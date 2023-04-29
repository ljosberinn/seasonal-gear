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
  id: string;
  name: string;
  journalEncounterItemId: string;
  journalEncounterId: string;
  journalEncounterName: string;
  journalInstanceId: string;
  journalInstanceName: string;
  itemClassId: string;
  itemSubClassId: string;
  inventoryType: string;
  quality: string;
  stats: Stat[];
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

export interface RandPropPoint {
  ID: string;
  DamageReplaceStatF: string;
  DamageSecondaryF: string;
  DamageReplaceStat: string;
  DamageSecondary: string;
  EpicF_0: string;
  EpicF_1: string;
  EpicF_2: string;
  EpicF_3: string;
  EpicF_4: string;
  SuperiorF_0: string;
  SuperiorF_1: string;
  SuperiorF_2: string;
  SuperiorF_3: string;
  SuperiorF_4: string;
  GoodF_0: string;
  GoodF_1: string;
  GoodF_2: string;
  GoodF_3: string;
  GoodF_4: string;
  Epic_0: string;
  Epic_1: string;
  Epic_2: string;
  Epic_3: string;
  Epic_4: string;
  Superior_0: string;
  Superior_1: string;
  Superior_2: string;
  Superior_3: string;
  Superior_4: string;
  Good_0: string;
  Good_1: string;
  Good_2: string;
  Good_3: string;
  Good_4: string;
}
