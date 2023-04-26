import { createReadStream, createWriteStream, existsSync } from "node:fs";
import { unlink, writeFile } from "node:fs/promises";
import { join } from "node:path";
import { Readable } from "node:stream";
import { finished } from "node:stream/promises";

import { parse } from "csv-parse";

import { allJournalInstancesToKeep } from "../config/instances";
import {
  isItemClass,
  isItemSparse,
  isItemSubClass,
  isJournalEncounter,
  isJournalEncounterItem,
  isJournalInstance,
} from "./item-blocks/types.guard";
import { ItemBlock } from "./item-blocks/types";
import { info } from "./item-blocks/log";
import { ensureDir } from "fs-extra";
import { format } from "prettier";

const downloadWagoToolsCsv = async (
  tableName: string,
  build: string,
  skipIfExisting: boolean = false
) => {
  const dataDirectory = join(process.cwd(), "data", "dbc");
  await ensureDir(dataDirectory);
  const pathToSave = join(dataDirectory, `${tableName}.csv`);
  if (existsSync(pathToSave)) {
    if (skipIfExisting) {
      return;
    }
    await unlink(pathToSave);
  }

  const response = await fetch(
    `https://wago.tools/db2/${tableName}/csv?build=${build}`
  );
  if (!response.body) {
    throw new Error(`Unable to retrieve table ${tableName} for build ${build}`);
  }

  const fileStream = createWriteStream(pathToSave, {
    flags: "wx",
    encoding: "utf-8",
  });
  // @ts-expect-error Browser/Node incompatibility
  await finished(Readable.fromWeb(response.body).pipe(fileStream));
};

const parseCsvIntoJson = async <T extends object>(
  tableName: string,
  typeGuard: (record: unknown) => record is T,
  filterFn: (record: T) => boolean = () => true
) => {
  const dataDirectory = join(process.cwd(), "data", "dbc");
  await ensureDir(dataDirectory);
  const pathToRead = join(dataDirectory, `${tableName}.csv`);
  const pathToSave = join(dataDirectory, `${tableName}.json`);

  const records: T[] = [];
  const parser = createReadStream(pathToRead).pipe(
    parse({
      columns: true,
      skip_empty_lines: true,
      skip_records_with_empty_values: true,
      skip_records_with_error: true,
    })
  );

  for await (const record of parser) {
    if (typeGuard(record) && filterFn(record)) {
      records.push(record);
    }
  }

  await writeFile(pathToSave, JSON.stringify(records), { encoding: "utf-8" });

  return records;
};

const generateItemBlocksTsFile = async (itemBlocks: ItemBlock[]) => {
  const dataDirectory = join(process.cwd(), "app", "models");
  await ensureDir(dataDirectory);
  const pathToSave = join(dataDirectory, "items.ts");

  const header = `
    /*
     * Generated items.
     * WARNING: Do not manually change this file.
     */
    import { associateBy } from "~/associate-by"; 
    import { typedKeys } from "~/typed-keys"; 
     
    export type Item = {
      id: number;
      name: string;
      journalEncounterItemId: number;
      journalEncounterId: number;
      journalEncounterName: string;
      journalInstanceId: number;
      journalInstanceName: string;
    };
    export type ItemsByJournalEncounterName = Record<string, Item[]>;
    export type ItemsByJournalInstanceName = Record<string, Item[]>;
    export type ItemsByJournalInstanceAndJournalEncounterName = Record<
      string,
      Record<string, Item[]>
    >;
    
    export const items: Item[] = [
  `.trim();
  const itemBlocksMappedToTs = itemBlocks
    .map((itemBlock) =>
      `
    {
        id: ${Number(itemBlock.id)},
        name: "${itemBlock.name}",
        journalEncounterItemId: ${Number(itemBlock.journalEncounterItemId)},
        journalEncounterId: ${Number(itemBlock.journalEncounterId)},
        journalEncounterName: "${itemBlock.journalEncounterName}",
        journalInstanceId: ${Number(itemBlock.journalInstanceId)},
        journalInstanceName: "${itemBlock.journalInstanceName}",
    },
  `.trim()
    )
    .join("\n");
  const footer = `
    ];
    
    export const itemsByJournalInstanceName: ItemsByJournalInstanceName =
      associateBy(items, (item) => item.journalInstanceName);
    export const itemsByJournalEncounterName: ItemsByJournalEncounterName =
      associateBy(items, (item) => item.journalEncounterName);
    export const itemsByJournalInstanceNameAndJournalEncounterName = typedKeys(
      itemsByJournalInstanceName
    ).reduce<ItemsByJournalInstanceAndJournalEncounterName>((acc, instanceName) => {
      const itemsForInstance = items.filter(
        (item) => item.journalInstanceName === instanceName
      );
      acc[instanceName] = associateBy(
        itemsForInstance,
        (item) => item.journalEncounterName
      );
      return acc;
    }, {});
  `.trim();

  const itemTsContents = format(`${header}${itemBlocksMappedToTs}${footer}`, {
    parser: "typescript",
  });

  await writeFile(pathToSave, itemTsContents, {
    encoding: "utf-8",
  });
};

const build = "10.1.0.49318";

(async () => {
  info("Starting ItemBlock generation...");

  info("Downloading DBC CSVs...");
  await downloadWagoToolsCsv("ItemSparse", build, true);
  await downloadWagoToolsCsv("ItemClass", build, true);
  await downloadWagoToolsCsv("ItemSubClass", build, true);
  await downloadWagoToolsCsv("JournalEncounter", build, true);
  await downloadWagoToolsCsv("JournalEncounterItem", build, true);
  await downloadWagoToolsCsv("JournalInstance", build, true);

  info("Converting DBC CSVs to JSON...");
  const journalInstances = await parseCsvIntoJson(
    "JournalInstance",
    isJournalInstance,
    (record) => allJournalInstancesToKeep.includes(record.Name_lang)
  );
  const journalInstanceIds = journalInstances.map((instance) => instance.ID);

  const journalEncounters = await parseCsvIntoJson(
    "JournalEncounter",
    isJournalEncounter,
    (record) => journalInstanceIds.includes(record.JournalInstanceID)
  );
  const journalEncounterIds = journalEncounters.map(
    (encounter) => encounter.ID
  );

  const journalEncounterItems = await parseCsvIntoJson(
    "JournalEncounterItem",
    isJournalEncounterItem,
    (record) => journalEncounterIds.includes(record.JournalEncounterID)
  );
  const journalEncounterItemItemIds = journalEncounterItems.map(
    (item) => item.ItemID
  );

  const itemSparses = await parseCsvIntoJson(
    "ItemSparse",
    isItemSparse,
    (record) => journalEncounterItemItemIds.includes(record.ID)
  );
  const itemClasses = await parseCsvIntoJson("ItemClass", isItemClass);
  const itemSubClasses = await parseCsvIntoJson("ItemSubClass", isItemSubClass);

  info("Collating Item Blocks...");
  const itemBlocks = itemSparses
    .map<ItemBlock | null>((itemSparse) => {
      const journalEncounterItem = journalEncounterItems.find(
        (journalEncounterItem) => journalEncounterItem.ItemID === itemSparse.ID
      );
      if (!journalEncounterItem) {
        console.log(
          `Unable to find matching JournalEncounterItem for item: id=${itemSparse.ID}`
        );
        return null;
      }

      const journalEncounter = journalEncounters.find(
        (journalEncounter) =>
          journalEncounter.ID === journalEncounterItem.JournalEncounterID
      );
      if (!journalEncounter) {
        console.log(
          `Unable to find matching JournalEncounter for item: id=${itemSparse.ID}`
        );
        return null;
      }

      const journalInstance = journalInstances.find(
        (journalInstance) =>
          journalInstance.ID === journalEncounter.JournalInstanceID
      );
      if (!journalInstance) {
        console.log(
          `Unable to find matching JournalInstance for item: id=${itemSparse.ID}`
        );
        return null;
      }

      return {
        id: itemSparse.ID,
        name: itemSparse.Display_lang,
        journalEncounterItemId: journalEncounterItem.ID,
        journalEncounterId: journalEncounter.ID,
        journalEncounterName: journalEncounter.Name_lang,
        journalInstanceId: journalInstance.ID,
        journalInstanceName: journalInstance.Name_lang,
      } satisfies ItemBlock;
    })
    .filter((block): block is ItemBlock => block !== null);

  info("Generating TypeScript for app to use...");
  await generateItemBlocksTsFile(itemBlocks);
})();
