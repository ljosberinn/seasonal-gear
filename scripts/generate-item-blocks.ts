import { createReadStream, createWriteStream, existsSync } from "node:fs";
import { unlink, writeFile } from "node:fs/promises";
import { join } from "node:path";
import { Readable } from "node:stream";
import { finished } from "node:stream/promises";

import { parse } from "csv-parse";

import { allJournalInstancesToKeep } from "../config/instances";
import {
  isItem,
  isItemClass,
  isItemSparse,
  isItemSubClass,
  isJournalEncounter,
  isJournalEncounterItem,
  isJournalInstance,
  isRandPropPoint,
} from "./item-blocks/types.guard";
import {
  ItemBlock,
  ItemClass,
  ItemSparse,
  ItemSubClass,
  RandPropPoint,
} from "./item-blocks/types";
import { info, warn, error } from "./item-blocks/log";
import { ensureDir } from "fs-extra";
import { format } from "prettier";
import { associateBy } from "~/associate-by";

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

// https://github.com/Marlamin/wow.tools/blob/master/dbc/js/enums.js#L508-L579
const itemPrettyStatType: Record<number, string> = {
  0: "Mana",
  1: "Health",
  3: "Agility",
  4: "Strength",
  5: "Intellect",
  6: "Spirit",
  7: "Stamina",
  12: "Defense",
  13: "Dodge",
  14: "Parry",
  15: "Block",
  16: "Hit (Melee)",
  17: "Hit (Ranged)",
  18: "Hit (Spell)",
  19: "Crit (Melee)",
  20: "Crit (Ranged)",
  21: "Crit (Spell)",
  22: "Corruption",
  23: "Corruption Resistance",
  24: "Random Stat 1",
  25: "Random Stat 2",
  26: "Critical Strike Avoidance (Ranged)",
  27: "Critical Strike Avoidance (Spell)",
  28: "Haste (Melee)",
  29: "Haste (Ranged)",
  30: "Haste (Spell)",
  31: "Hit",
  32: "Critical Strike",
  33: "Hit Avoidance",
  34: "Critical Strike Avoidance",
  35: "Resilience",
  36: "Haste",
  37: "Expertise",
  38: "Attack Power",
  39: "Attack Power (Ranged)",
  40: "Versatility",
  41: "Bonus Healing",
  42: "Bonus Damage",
  43: "Mana Regeneration",
  44: "Armor Penetration",
  45: "Spell Power",
  46: "Health Regen",
  47: "Spell Penetration",
  48: "Block",
  49: "Mastery",
  50: "Bonus Armor",
  51: "Fire Resistance",
  52: "Frost Resistance",
  53: "Holy Resistance",
  54: "Shadow Resistance",
  55: "Nature Resistance",
  56: "Arcane Resistance",
  57: "PvP Power",
  58: "Amplify",
  59: "Multistrike",
  60: "Readiness",
  61: "Speed",
  62: "Lifesteal",
  63: "Avoidance",
  64: "Sturdiness",
  65: "Unused (7)",
  66: "Cleave",
  67: "Versatility",
  68: "Unused (10)",
  69: "Unused (11)",
  70: "Unused (12)",
  71: "Agility | Strength | Intellect",
  72: "Agility | Strength",
  73: "Agility | Intellect",
  74: "Strength | Intellect",
};

const generateItemBlocksTsFile = async (
  itemBlocks: ItemBlock[],
  itemClasses: ItemClass[],
  itemSubClasses: ItemSubClass[]
) => {
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
    import { type ItemClass, type ItemSubClass, type Stat } from "scripts/item-blocks/types";
     

    export type Item = {
      id: number;
      name: string;
      journalEncounterItemId: number;
      journalEncounterId: number;
      journalEncounterName: string;
      journalInstanceId: number;
      journalInstanceName: string;
      itemClassId: number;
      itemSubClassId: number;
      inventoryType: number;
      stats: Stat[];
      quality: number;
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
        itemClassId: ${Number(itemBlock.itemClassId)},
        itemSubClassId: ${Number(itemBlock.itemSubClassId)},
        inventoryType: ${Number(itemBlock.inventoryType)},
        quality: ${Number(itemBlock.quality)},
        stats: ${JSON.stringify(itemBlock.stats)}
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

    export const itemStatEnum: Record<string, string> = ${JSON.stringify(
      itemPrettyStatType
    )}
  `.trim();

  const itemTsContents = format(`${header}${itemBlocksMappedToTs}${footer}`, {
    parser: "typescript",
  });

  await writeFile(pathToSave, itemTsContents, {
    encoding: "utf-8",
  });
};

const parseMultiplierTxt = (text: string): MultiplierByItemLevel[] => {
  const isKeyOfMultiplierByItemLevel = (
    str: string
  ): str is keyof MultiplierByItemLevel =>
    str === "itemLevel" ||
    str === "armorMultiplier" ||
    str === "weaponMultiplier" ||
    str === "trinketMultiplier" ||
    str === "jewelryMultiplier";

  const [headers, ...rows] = text.split("\n");

  const columns = headers.split("\t").map((column) => {
    return column
      .split(" ")
      .map((str, index) => (index === 0 ? str.toLowerCase() : str))
      .join("")
      .trim();
  });

  return rows.map<MultiplierByItemLevel>((row) => {
    const parts = row.replaceAll("\r", "").split("\t");

    return columns.reduce<MultiplierByItemLevel>(
      (acc, column, index) => {
        if (isKeyOfMultiplierByItemLevel(column)) {
          acc[column] = parts[index];
        } else {
          warn(`Saw unknown CombatRatingMultiplierColumn: "${column}"`);
        }

        return acc;
      },
      {
        itemLevel: "",
        armorMultiplier: "1",
        weaponMultiplier: "1",
        trinketMultiplier: "1",
        jewelryMultiplier: "1",
      }
    );
  });
};

const getStaminaMultiplierByItemLevel = (): Promise<
  MultiplierByItemLevel[]
> => {
  // https://wago.tools/files?search=staminamultbyilvl
  return fetch("https://wago.tools/api/casc/1980632?download")
    .then((response) => response.text())
    .then((text) => parseMultiplierTxt(text));
};

type MultiplierByItemLevel = {
  itemLevel: string;
  armorMultiplier: string;
  weaponMultiplier: string;
  trinketMultiplier: string;
  jewelryMultiplier: string;
};

const getCombatRatingsMultByILvl = (): Promise<MultiplierByItemLevel[]> => {
  // https://wago.tools/files?search=CombatRatingsMultByILvl
  return fetch("https://wago.tools/api/casc/1391670?download")
    .then((response) => response.text())
    .then((text) => parseMultiplierTxt(text));
};

const build = "10.1.0.49318";

(async () => {
  info("Starting ItemBlock generation...");

  info("Downloading DBC CSVs...");

  const [combatRatingsMultByILvl, staminaMultiplierByItemLevel] =
    await Promise.all([
      getCombatRatingsMultByILvl(),
      getStaminaMultiplierByItemLevel(),
      downloadWagoToolsCsv("ItemSparse", build, true),
      downloadWagoToolsCsv("ItemClass", build, true),
      downloadWagoToolsCsv("ItemSubClass", build, true),
      downloadWagoToolsCsv("JournalEncounter", build, true),
      downloadWagoToolsCsv("JournalEncounterItem", build, true),
      downloadWagoToolsCsv("JournalInstance", build, true),
      downloadWagoToolsCsv("Item", build, true),
      downloadWagoToolsCsv("RandPropPoints", build, true),
    ]);

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
  const item = await parseCsvIntoJson("Item", isItem);
  const randPropPoints = await parseCsvIntoJson(
    "RandPropPoints",
    isRandPropPoint
  );

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

      const itemMeta = item.find((item) => item.ID === itemSparse.ID);

      if (!itemMeta) {
        console.log(
          `Unable to find matching Item for item: id=${itemSparse.ID}`
        );
        return null;
      }

      const stats: ItemBlock["stats"] = [];

      for (let i = 0; i <= 9; i++) {
        const bonusStatKey = `StatModifier_bonusStat_${i}`;

        const bonusStat = isKeyOfItemSparse(bonusStatKey, itemSparse)
          ? itemSparse[bonusStatKey]
          : "-1";

        if (bonusStat === "-1") {
          continue;
        }

        const percentKey = `StatPercentEditor_${i}`;

        const percent = isKeyOfItemSparse(percentKey, itemSparse)
          ? itemSparse[percentKey]
          : "0";

        if (percent === "0") {
          continue;
        }

        const bonusStatId = Number(bonusStat);

        const beautifiedStatName =
          bonusStatId in itemPrettyStatType
            ? itemPrettyStatType[bonusStatId]
            : "";

        if (!beautifiedStatName) {
          continue;
        }

        const value = calculateStatValue(
          bonusStat,
          itemSparse.ItemLevel,
          percent,
          itemSparse.OverallQualityID,
          itemMeta.InventoryType,
          itemMeta.SubclassID,
          randPropPoints,
          combatRatingsMultByILvl,
          staminaMultiplierByItemLevel,
          itemSparse.ID === "56359"
        );

        if (itemSparse.ID === "56359") {
          error(`${beautifiedStatName} => ${value}`);
        }

        if (value <= 0) {
          continue;
        }

        stats.push({
          amount: value,
          name: beautifiedStatName,
        });
      }

      return {
        id: itemSparse.ID,
        name: itemSparse.Display_lang,
        journalEncounterItemId: journalEncounterItem.ID,
        journalEncounterId: journalEncounter.ID,
        journalEncounterName: journalEncounter.Name_lang,
        journalInstanceId: journalInstance.ID,
        journalInstanceName: journalInstance.Name_lang,
        itemClassId: itemMeta.ClassID,
        itemSubClassId: itemMeta.SubclassID,
        inventoryType: itemMeta.InventoryType,
        quality: itemSparse.OverallQualityID,
        stats,
      } satisfies ItemBlock;
    })
    .filter((block): block is ItemBlock => block !== null);

  info("Generating TypeScript for app to use...");
  await generateItemBlocksTsFile(itemBlocks, itemClasses, itemSubClasses);
})();

const isKeyOfItemSparse = (
  str: string,
  itemSparse: ItemSparse
): str is keyof ItemSparse => str in itemSparse;

const getRandomPropertyKey = (
  quality: string,
  inventoryType: string,
  itemSubClassId: string
): string => {
  let targetIndex = -1;

  switch (inventoryType) {
    case "1":
    case "4":
    case "5":
    case "7":
    case "15":
    case "17":
    case "20":
    case "25": {
      targetIndex = 0;
      break;
    }
    case "3":
    case "6":
    case "8":
    case "10":
    case "12": {
      targetIndex = 1;
      break;
    }
    case "2":
    case "9":
    case "11":
    case "16": {
      targetIndex = 2;
      break;
    }
    case "13":
    case "14":
    case "21":
    case "22":
    case "23": {
      targetIndex = 3;
      break;
    }
    case "26":
      targetIndex = 3;
      if (itemSubClassId !== "19") {
        targetIndex = 0;
      }
      break;
    case "28": {
      targetIndex = 4;
      break;
    }
    default: {
      break;
    }
  }

  let targetField = "Good";

  switch (quality) {
    case "2": {
      break;
    }
    case "3": {
      targetField = "Superior";
      break;
    }
    case "4": {
      targetField = "Epic";
    }
  }

  return `${targetField}_${targetIndex}`;
};

const calculateStatValue = (
  bonusStat: string,
  itemLevel: string,
  statAlloc: string,
  quality: string,
  inventoryType: string,
  itemSubClassId: string,
  randPropPoints: RandPropPoint[],
  combatRatingsMultByILvl: MultiplierByItemLevel[],
  staminaMultiplierByItemLevel: MultiplierByItemLevel[],
  shouldLog: boolean
): number => {
  const randomPropRow = randPropPoints.find((prop) => prop.ID === itemLevel);

  if (!randomPropRow) {
    return 0;
  }

  const randomPropKey = getRandomPropertyKey(
    quality,
    inventoryType,
    itemSubClassId
  );

  const value = isKeyOfRandProp(randomPropKey, randomPropRow)
    ? randomPropRow[randomPropKey]
    : "0";

  if (value === "0") {
    return 0;
  }

  const baseStatAllocation = Math.floor(
    Number(statAlloc) * Number(value) * 0.000099999997 + 0.5
  );

  const multiplierRow = findItemlevelCombatRatingMultiplier(
    isStatCombatRating(bonusStat)
      ? combatRatingsMultByILvl
      : bonusStat === "7"
      ? staminaMultiplierByItemLevel
      : [],
    itemLevel
  );

  if (!multiplierRow) {
    return baseStatAllocation;
  }

  // https://github.com/Marlamin/DBCDumpHost/blob/master/DBCDumpHost/Utils/TooltipUtils.cs#L316-L338
  const multiplier = Number(
    inventoryType === "2" || inventoryType === "11"
      ? multiplierRow.jewelryMultiplier
      : inventoryType === "12"
      ? multiplierRow.trinketMultiplier
      : inventoryType === "13" ||
        inventoryType === "14" ||
        inventoryType === "17" ||
        inventoryType === "21" ||
        inventoryType === "22" ||
        inventoryType === "23" ||
        inventoryType === "26" ||
        inventoryType === "15"
      ? multiplierRow.weaponMultiplier
      : multiplierRow.armorMultiplier
  );

  if (shouldLog) {
    warn({
      inventoryType,
      itemSubClassId,
      randomPropRow,
      randomPropKey,
      value,
      baseStatAllocation,
      statAlloc,
      multiplierRow,
      multiplier,
    });
  }

  return Math.floor(baseStatAllocation * multiplier);
};

const findItemlevelCombatRatingMultiplier = (
  multipliersByItemlevel: MultiplierByItemLevel[],
  itemLevel: string
): MultiplierByItemLevel | null => {
  const asNumber = Number(itemLevel) - 1;

  // implies this is NEITHER a StatCombatRating nor Stamina, so we default to 1 for all
  // https://github.com/Marlamin/DBCDumpHost/blob/master/DBCDumpHost/Utils/TooltipUtils.cs#L298-L313
  if (multipliersByItemlevel.length === 0) {
    return {
      armorMultiplier: "1",
      itemLevel: `${asNumber}`,
      jewelryMultiplier: "1",
      trinketMultiplier: "1",
      weaponMultiplier: "1",
    };
  }

  const match = multipliersByItemlevel.find(
    (dataset) => Number(dataset.itemLevel) === asNumber
  );

  return match ? match : null;
};

const isKeyOfRandProp = (
  str: string,
  randProp: RandPropPoint
): str is keyof RandPropPoint => str in randProp;

const combatRatingStats = new Set([
  "13", // Dodge
  "14", // Parry
  "15", // Block
  "16", // Hit Melee
  "17", // Hit Ranged
  "18", // Hit Spell
  "19", // Crit Melee
  "20", // Crit Ranged
  "31", // Hit
  "32", // Crit
  "35", // Resilience
  "36", // Haste
  "37", // Expertise
  "40", // Versatility
  "49", // Mastery
  "59", // Multistrike
  "61", // Speed
  "62", // Leech
  "63", // Avoidance
  "64", // Sturdiness
]);

const isStatCombatRating = (stat: string): boolean => {
  return combatRatingStats.has(stat);
};
