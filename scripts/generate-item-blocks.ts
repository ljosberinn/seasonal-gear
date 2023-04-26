import { createReadStream, createWriteStream, existsSync } from "node:fs";
import { mkdir, unlink, writeFile } from "node:fs/promises";
import { join } from "node:path";
import { Readable } from "node:stream";
import { finished } from "node:stream/promises";

import { parse } from "csv-parse";
import minimist from "minimist";

import { allJournalInstancesToKeep } from "../config/instances";

const downloadWagoToolsCsv = async (
  tableName: string,
  build: string,
  skipIfExisting: boolean = false
) => {
  const dataDirectory = join(process.cwd(), "data");
  const pathToSave = join(dataDirectory, `${tableName}.csv`);
  if (!existsSync(dataDirectory)) {
    await mkdir(dataDirectory);
  }
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
  // @ts-ignore
  await finished(Readable.fromWeb(response.body).pipe(fileStream));
};

const parseCsvIntoJson = async (
  tableName: string,
  filterFn: (record: any) => boolean = (record) => !!record
) => {
  const dataDirectory = join(process.cwd(), "data");
  const pathToRead = join(dataDirectory, `${tableName}.csv`);
  const pathToSave = join(dataDirectory, `${tableName}.json`);

  const records = [];
  const parser = createReadStream(pathToRead).pipe(
    parse({
      columns: true,
      skip_empty_lines: true,
      skip_records_with_empty_values: true,
      skip_records_with_error: true,
    })
  );

  for await (const record of parser) {
    if (filterFn(record)) {
      records.push(record);
    }
  }

  await writeFile(pathToSave, JSON.stringify(records), { encoding: "utf-8" });

  return records;
};

const argv = minimist(process.argv.splice(2));
const build = argv.length > 0 ? argv[0] : "10.1.0.49255";

(async () => {
  await downloadWagoToolsCsv("ItemSparse", build, true);
  await downloadWagoToolsCsv("ItemClass", build, true);
  await downloadWagoToolsCsv("ItemSubClass", build, true);
  await downloadWagoToolsCsv("JournalEncounter", build, true);
  await downloadWagoToolsCsv("JournalEncounterItem", build, true);
  await downloadWagoToolsCsv("JournalInstance", build, true);

  const journalInstances = await parseCsvIntoJson("JournalInstance", (record) =>
    allJournalInstancesToKeep.includes(record.Name_lang)
  );
  const journalInstanceIds = journalInstances.map((instance) => instance.ID);

  const journalEncounters = await parseCsvIntoJson(
    "JournalEncounter",
    (record) => journalInstanceIds.includes(record.JournalInstanceID)
  );
  const journalEncounterIds = journalEncounters.map(
    (encounter) => encounter.ID
  );

  const journalEncounterItems = await parseCsvIntoJson(
    "JournalEncounterItem",
    (record) => journalEncounterIds.includes(record.JournalEncounterID)
  );
  const journalEncounterItemItemIds = journalEncounterItems.map(
    (item) => item.ItemID
  );

  const items = await parseCsvIntoJson("ItemSparse", (record) =>
    journalEncounterItemItemIds.includes(record.ID)
  );
  await parseCsvIntoJson("ItemClass");
  await parseCsvIntoJson("ItemSubClass");
})();
