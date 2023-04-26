export const journalInstanceToKeepBySeason: string[][] = [
  // DF S2
  [
    "Aberrus",
    "Halls of Infusion",
    "Brackenhide Hollow",
    "Uldaman: Legacy of Tyr",
    "Neltharus",
    "Neltharion's Lair",
    "Freehold",
    "Underrot",
    "Vortex Pinnacle",
  ],
];
export const allJournalInstancesToKeep = journalInstanceToKeepBySeason.flatMap(season => season);
