export interface ItemClass {
  ID: string;
  ClassName_lang: string;
  ClassID: string;
  PriceModifier: string;
  Flags: string;
}

export interface ItemSubClass {
  DisplayName_lang: string;
  VerboseName_lang: string;
  ID: string;
  ClassID: string;
  SubClassID: string;
  AuctionHouseSortOrder: string;
  PrerequisiteProficiency: string;
  Flags: string;
  DisplayFlags: string;
  WeaponSwingSize: string;
  PostrequisiteProficiency: string;
}

export interface ItemSparse {
  ID: string;
  AllowableRace: string;
  Description_lang: string;
  Display3_lang: string;
  Display2_lang: string;
  Display1_lang: string;
  Display_lang: string;
  ExpansionID: string;
  DmgVariance: string;
  LimitCategory: string;
  DurationInInventory: string;
  QualityModifier: string;
  BagFamily: string;
  StartQuestID: string;
  LanguageID: string;
  ItemRange: string;
  StatPercentageOfSocket_0: string;
  StatPercentageOfSocket_1: string;
  StatPercentageOfSocket_2: string;
  StatPercentageOfSocket_3: string;
  StatPercentageOfSocket_4: string;
  StatPercentageOfSocket_5: string;
  StatPercentageOfSocket_6: string;
  StatPercentageOfSocket_7: string;
  StatPercentageOfSocket_8: string;
  StatPercentageOfSocket_9: string;
  StatPercentEditor_0: string;
  StatPercentEditor_1: string;
  StatPercentEditor_2: string;
  StatPercentEditor_3: string;
  StatPercentEditor_4: string;
  StatPercentEditor_5: string;
  StatPercentEditor_6: string;
  StatPercentEditor_7: string;
  StatPercentEditor_8: string;
  StatPercentEditor_9: string;
  Stackable: string;
  MaxCount: string;
  MinReputation: string;
  RequiredAbility: string;
  SellPrice: string;
  BuyPrice: string;
  VendorStackCount: string;
  PriceVariance: string;
  PriceRandomValue: string;
  Flags_0: string;
  Flags_1: string;
  Flags_2: string;
  Flags_3: string;
  OppositeFactionItemID: string;
  ModifiedCraftingReagentItemID: string;
  ContentTuningID: string;
  PlayerLevelToItemLevelCurveID: string;
  ItemNameDescriptionID: string;
  RequiredTransmogHoliday: string;
  RequiredHoliday: string;
  Gem_properties: string;
  Socket_match_enchantment_ID: string;
  TotemCategoryID: string;
  InstanceBound: string;
  ZoneBound_0: string;
  ZoneBound_1: string;
  ItemSet: string;
  LockID: string;
  PageID: string;
  ItemDelay: string;
  MinFactionID: string;
  RequiredSkillRank: string;
  RequiredSkill: string;
  ItemLevel: string;
  AllowableClass: string;
  ArtifactID: string;
  SpellWeight: string;
  SpellWeightCategory: string;
  SocketType_0: string;
  SocketType_1: string;
  SocketType_2: string;
  SheatheType: string;
  Material: string;
  PageMaterialID: string;
  Bonding: string;
  DamageType: string;
  StatModifier_bonusStat_0: string;
  StatModifier_bonusStat_1: string;
  StatModifier_bonusStat_2: string;
  StatModifier_bonusStat_3: string;
  StatModifier_bonusStat_4: string;
  StatModifier_bonusStat_5: string;
  StatModifier_bonusStat_6: string;
  StatModifier_bonusStat_7: string;
  StatModifier_bonusStat_8: string;
  StatModifier_bonusStat_9: string;
  ContainerSlots: string;
  RequiredPVPMedal: string;
  RequiredPVPRank: string;
  RequiredLevel: string;
  InventoryType: string;
  OverallQualityID: string;
}

export interface JournalEncounter {
  Name_lang: string;
  Description_lang: string;
  Map_0: string;
  Map_1: string;
  ID: string;
  JournalInstanceID: string;
  DungeonEncounterID: string;
  OrderIndex: string;
  FirstSectionID: string;
  UiMapID: string;
  MapDisplayConditionID: string;
  Flags: string;
  DifficultyMask: string;
}

export interface JournalEncounterItem {
  ID: string;
  JournalEncounterID: string;
  ItemID: string;
  FactionMask: string;
  Flags: string;
  DifficultyMask: string;
}

export interface JournalInstance {
  ID: string;
  Name_lang: string;
  Description_lang: string;
  MapID: string;
  BackgroundFileDataID: string;
  ButtonFileDataID: string;
  ButtonSmallFileDataID: string;
  LoreFileDataID: string;
  Flags: string;
  AreaID: string;
}

export type Stat = {
  name: string;
  amount: number;
};

export interface ItemBlock {
  id: number;
  name: string;
  journalEncounterItemId: number | null;
  journalEncounterId: number | null;
  journalEncounterName: string | null;
  journalInstanceId: number | null;
  journalInstanceName: string | null;
  itemClassId: number;
  itemSubClassId: number;
  inventoryType: number;
  quality: number;
  stats: Stat[];
  itemSetId: number | null;
}

export interface Item {
  ID: string;
  ClassID: string;
  SubclassID: string;
  Material: string;
  InventoryType: string;
  SheatheType: string;
  Sound_override_subclassID: string;
  IconFileDataID: string;
  ItemGroupSoundsID: string;
  ContentTuningID: string;
  ModifiedCraftingReagentItemID: string;
  CraftingQualityID: string;
}

export interface JournalTier {
  ID: string;
  Name_lang: string;
  PlayerConditionID: string;
}

export interface JournalTierXInstance {
  ID: string;
  JournalTierID: string;
  JournalInstanceID: string;
  OrderIndex: string;
  // Field_10_1_0_49092_003: string;
}

export interface ItemSet {
  ID: string;
  Name_lang: string;
  SetFlags: string;
  RequiredSkill: string;
  RequiredSkillRank: string;
  ItemID_0: string;
  ItemID_1: string;
  ItemID_2: string;
  ItemID_3: string;
  ItemID_4: string;
  ItemID_5: string;
  ItemID_6: string;
  ItemID_7: string;
  ItemID_8: string;
  ItemID_9: string;
  ItemID_10: string;
  ItemID_11: string;
  ItemID_12: string;
  ItemID_13: string;
  ItemID_14: string;
  ItemID_15: string;
  ItemID_16: string;
}

export interface CraftingData {
  ID: string;
  // Field_10_0_0_44649_001: string;
  CraftingDifficultyID: string;
  CraftedItemID: string;
  ItemBonusTreeID: string;
  CraftingDifficulty: string;
  // Field_10_0_0_44649_005: string;
  CraftSkillBonusPercent: string;
  ReCraftSkillBonusPercent: string;
  InspirationSkillBonusPercent: string;
  // Field_10_0_0_44649_009: string;
  // Field_10_0_0_45141_011: string;
  FirstCraftFlagQuestID: string;
  FirstCraftTreasureID: string;
  CraftedTreasureID: string;
}
