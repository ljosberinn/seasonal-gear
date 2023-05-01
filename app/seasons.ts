export enum Regions {
  eu = "eu",
  us = "us",
  kr = "kr",
  tw = "tw",
}

enum ArmorSlot {
  HEAD = 1,
  NECK = 2,
  SHOULDER = 3,
  SHIRT = 4,
  CHEST = 5,
  BELT = 6,
  LEGS = 7,
  FEET = 8,
  WRIST = 9,
  GLOVES = 10,
  FINGER1 = 11,
  FINGER2 = 12, // do not use; identical with below
  TRINKET1 = 13,
  TRINKET2 = 14, // do not use; identical with below
  BACK = 15,
  MAINHAND = 16,
  OFFHAND = 17,
  TABARD = 19,
}

enum ArmorType {
  CLOTH = 0,
  LEATHER = 1,
  MAIL = 2,
  PLATE = 3,
}

enum Class {
  WARRIOR = 1,
  PALADIN = 2,
  HUNTER = 3,
  ROGUE = 4,
  PRIEST = 5,
  DEATHKNIGHT = 6,
  SHAMAN = 7,
  MAGE = 8,
  WARLOCK = 9,
  MONK = 10,
  DRUID = 11,
  DEMONHUNTER = 12,
  EVOKER = 13,
}

type Item = {
  id: number;
  /**
   * key of meta.sources
   */
  source: number;
  slot: ArmorSlot;
  armorType: ArmorType;
  classIds?: Class[];
  setId: number | null;
};

type Source = {
  kind: "raid" | "dungeon" | "crafting";
  name: string;
};

export type Season = {
  name: string;
  slug: string;
  startDates: Record<Regions, number | null>;
  endDates: Record<Regions, number | null>;
  seasonIcon: string;
  usePtrTooltip: boolean;
  sources: Record<number, Source>;
  itemsBySource: Record<number, Item[]>;
  /**
   * @see https://github.com/WoWAnalyzer/WoWAnalyzer/blob/dragonflight/src/common/ITEMS/dragonflight/tier.ts
   */
  setIds: Record<Class, number> | null;
};

const UNKNOWN_SEASON_START_OR_ENDING = null;

export const seasons: Season[] = [
  {
    name: "DF S2",
    slug: "df-season-2",
    // Actual S2 start dates
    // startDates: {
    //   us: 1_683_644_400_000,
    //   eu: 1_683_691_200_000,
    //   kr: 1_683_759_600_000,
    //   tw: 1_683_759_600_000,
    // },
    // TODO: remove these and replace with actual S2 start dates
    startDates: {
      us: 1_670_943_600_000,
      eu: 1_670_990_400_000,
      kr: 1_671_058_800_000,
      tw: 1_671_058_800_000,
    },
    endDates: {
      us: UNKNOWN_SEASON_START_OR_ENDING,
      eu: UNKNOWN_SEASON_START_OR_ENDING,
      kr: UNKNOWN_SEASON_START_OR_ENDING,
      tw: UNKNOWN_SEASON_START_OR_ENDING,
    },
    seasonIcon:
      "https://wow.zamimg.com/images/wow/icons/small/inv_misc_head_dragon_black_nightmare.jpg",
    usePtrTooltip: true,
    itemsBySource: {},
    sources: {},
    setIds: {
      [Class.DEATHKNIGHT]: 1540,
      [Class.DEMONHUNTER]: 1541,
      [Class.DRUID]: 1542,
      [Class.EVOKER]: 1543,
      [Class.HUNTER]: 1544,
      [Class.MAGE]: 1545,
      [Class.MONK]: 1546,
      [Class.PALADIN]: 1547,
      [Class.PRIEST]: 1548,
      [Class.ROGUE]: 1549,
      [Class.SHAMAN]: 1550,
      [Class.WARLOCK]: 1551,
      [Class.WARRIOR]: 1552,
    },
  },
  {
    name: "DF S1",
    slug: "df-season-1",
    startDates: {
      us: 1_670_943_600_000,
      eu: 1_670_990_400_000,
      kr: 1_671_058_800_000,
      tw: 1_671_058_800_000,
    },
    endDates: {
      us: 1_683_007_200_000,
      eu: 1_683_064_800_000,
      kr: 1_683_118_800_000,
      tw: 1_683_118_800_000,
    },
    seasonIcon:
      "https://wow.zamimg.com/images/wow/icons/small/shaman_pvp_leaderclan.jpg",
    usePtrTooltip: false,
    itemsBySource: {},
    sources: {},
    setIds: {
      [Class.DEATHKNIGHT]: 1526,
      [Class.DEMONHUNTER]: 1527,
      [Class.DRUID]: 1528,
      [Class.EVOKER]: 1529,
      [Class.HUNTER]: 1530,
      [Class.MAGE]: 1531,
      [Class.MONK]: 1532,
      [Class.PALADIN]: 1533,
      [Class.PRIEST]: 1534,
      [Class.ROGUE]: 1535,
      [Class.SHAMAN]: 1536,
      [Class.WARLOCK]: 1537,
      [Class.WARRIOR]: 1538,
    },
  },
];

export const hasSeasonEndedForAllRegions = (slug: string): boolean => {
  const season = seasons.find((season) => season.slug === slug);

  if (!season) {
    return true;
  }

  const endDates = Object.values(season.endDates);

  if (endDates.includes(UNKNOWN_SEASON_START_OR_ENDING)) {
    return false;
  }

  const now = Date.now();

  return endDates.every((date) => now >= (date ?? 0));
};

export const findSeasonByTimestamp = (
  timestamp = Date.now()
): Season | null => {
  const season = seasons.find(
    (season) =>
      Object.values(season.startDates).some(
        (start) => start && timestamp >= start
      ) &&
      Object.values(season.endDates).some(
        (end) => end === UNKNOWN_SEASON_START_OR_ENDING || end > timestamp
      )
  );

  return season ?? null;
};

export const findSeasonByName = (slug: string): Season | null => {
  if (slug === "latest") {
    const ongoingSeason = findSeasonByTimestamp();

    if (ongoingSeason) {
      return ongoingSeason;
    }

    const mostRecentlyStartedSeason = seasons.find(
      (season) =>
        season.startDates.us !== null && Date.now() >= season.startDates.us
    );

    if (mostRecentlyStartedSeason) {
      return mostRecentlyStartedSeason;
    }
  }

  const match = seasons.find((season) => {
    return season.slug === slug;
  });

  return match ?? null;
};
