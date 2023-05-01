import { createReadStream, createWriteStream, existsSync } from "node:fs";
import { unlink, writeFile } from "node:fs/promises";
import { join } from "node:path";
import { Readable } from "node:stream";
import { finished } from "node:stream/promises";

import { parse } from "csv-parse";

import {
  isCraftingData,
  isItem,
  isItemClass,
  isItemSet,
  isItemSparse,
  isItemSubClass,
  isJournalEncounter,
  isJournalEncounterItem,
  isJournalInstance,
  isJournalTier,
  isJournalTierXInstance,
} from "./item-blocks/types.guard";
import {
  ItemBlock,
  ItemClass,
  ItemSet,
  ItemSubClass,
  JournalEncounter,
  JournalInstance,
} from "./item-blocks/types";
import { info } from "./item-blocks/log";
import { ensureDir } from "fs-extra";
import { format } from "prettier";
import { associateBy } from "~/associate-by";
import {
  authenticateWithBlizzard,
  retrieveAndParseStatsFromBlizzard,
} from "./bnet";
import { chunkArray } from "~/partition";
import { seasons } from "~/seasons";

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

// https://github.com/Marlamin/wow.tools/blob/master/dbc/js/enums.js#L2208-L2238
const inventoryTypeEnum: Record<number, string> = {
  0: "Non-equippable",
  1: "Head",
  2: "Neck",
  3: "Shoulder",
  4: "Shirt",
  5: "Chest",
  6: "Waist",
  7: "Legs",
  8: "Feet",
  9: "Wrist",
  10: "Hands",
  11: "Finger",
  12: "Trinket",
  13: "One-Hand",
  14: "Off Hand",
  15: "Ranged",
  16: "Back",
  17: "Two-Hand",
  18: "Bag",
  19: "Tabard",
  20: "Chest",
  21: "Main Hand",
  22: "Off Hand",
  23: "Held in Off-hand",
  24: "Ammo",
  25: "Thrown",
  26: "Ranged",
  27: "Quiver",
  28: "Relic",
};

// https://github.com/Marlamin/wow.tools/blob/master/dbc/js/enums.js#LL2546C1-L2556C2
const itemQuality: Record<number, string> = {
  0: "Poor",
  1: "Common",
  2: "Uncommon",
  3: "Rare",
  4: "Epic",
  5: "Legendary",
  6: "Artifact",
  7: "Heirloom",
  8: "WoW Token",
};

const generateItemBlocksTsFile = async (
  itemBlocks: ItemBlock[],
  itemClasses: ItemClass[],
  itemSubClasses: ItemSubClass[],
  itemSets: ItemSet[]
) => {
  const dataDirectory = join(process.cwd(), "app", "models");
  await ensureDir(dataDirectory);
  const pathToSave = join(dataDirectory, "items.ts");

  const header = `
    /*
     * Generated items.
     * WARNING: Do not manually change this file.
     */
    // import { associateBy } from "~/associate-by"; 
    // import { typedKeys } from "~/typed-keys";
    import { type ItemClass, type ItemBlock, type ItemSubClass, type ItemSet } from "scripts/item-blocks/types";
     
    export type Item = ItemBlock;
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
        id: ${itemBlock.id},
        name: "${itemBlock.name.replaceAll('"', "'")}",
        journalEncounterItemId: ${itemBlock.journalEncounterItemId},
        journalEncounterId: ${itemBlock.journalEncounterId},
        journalEncounterName: ${
          itemBlock.journalEncounterName
            ? `"${itemBlock.journalEncounterName}"`
            : null
        },
        journalInstanceId: ${itemBlock.journalInstanceId},
        journalInstanceName: ${
          itemBlock.journalInstanceName
            ? `"${itemBlock.journalInstanceName}"`
            : null
        },
        itemClassId: ${Number(itemBlock.itemClassId)},
        itemSubClassId: ${itemBlock.itemSubClassId},
        inventoryType: ${itemBlock.inventoryType},
        quality: ${itemBlock.quality},
        stats: ${JSON.stringify(itemBlock.stats)},
        itemSetId: ${itemBlock.itemSetId}
    },
  `.trim()
    )
    .join("\n");

  const itemClassMap = associateBy(itemClasses, (itemClass) => itemClass.ID);
  const itemSubClassMap = associateBy(
    itemSubClasses,
    (itemSubClass) => itemSubClass.ID
  );

  const footer = `
    ];

    // export const itemsByJournalInstanceName: ItemsByJournalInstanceName =
    //  associateBy(items, (item) => item.journalInstanceName);
    // export const itemsByJournalEncounterName: ItemsByJournalEncounterName =
    //   associateBy(items, (item) => item.journalEncounterName);
    // export const itemsByJournalInstanceNameAndJournalEncounterName = typedKeys(
    //   itemsByJournalInstanceName
    // ).reduce<ItemsByJournalInstanceAndJournalEncounterName>((acc, instanceName) => {
    //   const itemsForInstance = items.filter(
    //    (item) => item.journalInstanceName === instanceName
    //  );
    //  acc[instanceName] = associateBy(
    //    itemsForInstance,
    //    (item) => item.journalEncounterName
    //  );
    //  return acc;
    // }, {});

    export const itemClasses: Record<string, [ItemClass]> = ${JSON.stringify(
      itemClassMap
    )};
    export const itemSubClasses: Record<string, [ItemSubClass]> = ${JSON.stringify(
      itemSubClassMap
    )};

    export const inventoryTypeEnum: Record<string, string> = ${JSON.stringify(
      inventoryTypeEnum
    )};

    export const itemQualityEnum: Record<string, string> = ${JSON.stringify(
      itemQuality
    )}

    export const itemSets: ItemSet[] = ${JSON.stringify(itemSets)}
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

  await Promise.all([
    downloadWagoToolsCsv("ItemSparse", build, true),
    downloadWagoToolsCsv("ItemClass", build, true),
    downloadWagoToolsCsv("ItemSubClass", build, true),
    downloadWagoToolsCsv("JournalEncounter", build, true),
    downloadWagoToolsCsv("JournalEncounterItem", build, true),
    downloadWagoToolsCsv("JournalInstance", build, true),
    downloadWagoToolsCsv("Item", build, true),
    downloadWagoToolsCsv("JournalTier", build, true),
    downloadWagoToolsCsv("JournalTierXInstance", build, true),
    downloadWagoToolsCsv("ItemSet", build, true),
    downloadWagoToolsCsv("CraftingData", build, true),
  ]);

  info("Converting DBC CSVs to JSON...");

  const journalTiers = await parseCsvIntoJson(
    "JournalTier",
    isJournalTier,
    (record) =>
      record.Name_lang === "Dragonflight" ||
      record.Name_lang === "Mythic+ Dungeons"
  );

  const journalTierIDs = new Set(journalTiers.map((tier) => tier.ID));

  const journalTierXInstances = await parseCsvIntoJson(
    "JournalTierXInstance",
    isJournalTierXInstance,
    (record) => journalTierIDs.has(record.JournalTierID)
  );

  const journalInstanceIds = new Set(
    journalTierXInstances.map(
      (tierXInstance) => tierXInstance.JournalInstanceID
    )
  );

  const journalInstances = await parseCsvIntoJson(
    "JournalInstance",
    isJournalInstance,
    (record) => journalInstanceIds.has(record.ID)
  );

  const journalEncounters = await parseCsvIntoJson(
    "JournalEncounter",
    isJournalEncounter,
    (record) => journalInstanceIds.has(record.JournalInstanceID)
  );

  const journalEncounterIds = new Set(
    journalEncounters.map((encounter) => encounter.ID)
  );

  const journalEncounterItems = await parseCsvIntoJson(
    "JournalEncounterItem",
    isJournalEncounterItem,
    (record) => journalEncounterIds.has(record.JournalEncounterID)
  );

  const journalEncounterItemItemIds = new Set(
    journalEncounterItems.map((item) => item.ItemID)
  );

  const itemSet = await parseCsvIntoJson("ItemSet", isItemSet);
  const relevantItemSetIds = new Set(
    seasons.flatMap((season) => Object.values(season.setIds ?? {}))
  );

  itemSet.forEach((set) => {
    if (relevantItemSetIds.has(Number(set.ID))) {
      Object.entries(set).forEach(([key, value]) => {
        if (key.startsWith("ItemID_") && value !== "0") {
          journalEncounterItemItemIds.add(value);
        }
      });
    }
  });

  const craftingData = await parseCsvIntoJson("CraftingData", isCraftingData);

  craftingData.forEach((dataset) => {
    if (dataset.CraftedItemID !== "0") {
      journalEncounterItemItemIds.add(dataset.CraftedItemID);
    }
  });

  const [itemSparses, itemClasses, itemSubClasses, item] = await Promise.all([
    parseCsvIntoJson("ItemSparse", isItemSparse, (record) =>
      journalEncounterItemItemIds.has(record.ID)
    ),
    parseCsvIntoJson("ItemClass", isItemClass),
    parseCsvIntoJson("ItemSubClass", isItemSubClass),
    parseCsvIntoJson("Item", isItem, (record) =>
      journalEncounterItemItemIds.has(record.ID)
    ),
  ]);

  info("Authenticating with Blizzard...");

  const accessToken = await authenticateWithBlizzard();

  if (!accessToken) {
    throw new Error("Could not authenticate with Blizzard!");
  }

  info(`Collating ${itemSparses.length} Item Blocks...`);

  const itemBlocks: ItemBlock[] = [];
  const chunks = chunkArray(itemSparses, 100);

  for (const chunk of chunks) {
    const position = chunks.indexOf(chunk);
    const percent =
      position === 0
        ? "0.00%"
        : `${((position / (chunks.length - 1)) * 100).toFixed(2)}%`;
    info(`> Begin processing chunk ${chunks.indexOf(chunk) + 1} (${percent})`);

    await Promise.all(
      chunk.map(async (itemSparse) => {
        const journalEncounterItem = journalEncounterItems.find(
          (journalEncounterItem) =>
            journalEncounterItem.ItemID === itemSparse.ID
        );

        let journalEncounter: null | JournalEncounter = null;
        let journalInstance: null | JournalInstance = null;

        if (journalEncounterItem) {
          const encounter = journalEncounters.find(
            (journalEncounter) =>
              journalEncounter.ID === journalEncounterItem.JournalEncounterID
          );
          if (!encounter) {
            console.log(
              `Unable to find matching JournalEncounter for item: id=${itemSparse.ID}`
            );
            return null;
          }

          const instance = journalInstances.find(
            (journalInstance) =>
              journalInstance.ID === encounter.JournalInstanceID
          );

          if (!instance) {
            console.log(
              `Unable to find matching JournalInstance for item: id=${itemSparse.ID}`
            );
            return null;
          }

          journalEncounter = encounter;
          journalInstance = instance;
        }

        const itemMeta = item.find((item) => item.ID === itemSparse.ID);

        if (!itemMeta) {
          console.log(
            `Unable to find matching Item for item: id=${itemSparse.ID}`
          );
          return null;
        }

        if (itemMeta.InventoryType === "0") {
          return null; // ignore Non-Equippable (like toys and consumables)
        }

        const blizzardItemData = await retrieveAndParseStatsFromBlizzard(
          itemSparse.ID,
          accessToken
        );

        const stats: ItemBlock["stats"] =
          "preview_item" in blizzardItemData &&
          blizzardItemData.preview_item.stats
            ? blizzardItemData.preview_item.stats.map((stat) => ({
                amount: stat.value,
                name: stat.type.name,
              }))
            : []; // PTR Items currently have no stats since they aren't present in the API yet

        itemBlocks.push({
          id: Number(itemSparse.ID),
          name: itemSparse.Display_lang,
          journalEncounterItemId: journalEncounterItem
            ? Number(journalEncounterItem.ID)
            : null,
          journalEncounterId: journalEncounter
            ? Number(journalEncounter.ID)
            : null,
          journalEncounterName: journalEncounter
            ? journalEncounter.Name_lang
            : null,
          journalInstanceId: journalInstance
            ? Number(journalInstance.ID)
            : null,
          journalInstanceName: journalInstance
            ? journalInstance.Name_lang
            : null,
          itemClassId: Number(itemMeta.ClassID),
          itemSubClassId: Number(itemMeta.SubclassID),
          inventoryType: Number(itemMeta.InventoryType),
          quality: Number(itemSparse.OverallQualityID),
          stats,
          itemSetId:
            itemSparse.ItemSet === "0" ? null : Number(itemSparse.ItemSet),
        });
      })
    );

    await new Promise((resolve) => setTimeout(resolve, 1000));
  }

  info(`Result: ${itemBlocks.length} Item Blocks`);

  info("Generating TypeScript for app to use...");
  await generateItemBlocksTsFile(
    itemBlocks,
    itemClasses,
    itemSubClasses,
    itemSet
  );
})();
