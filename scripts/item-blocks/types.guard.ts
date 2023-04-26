/*
 * Generated type guards for "types.ts".
 * WARNING: Do not manually change this file.
 */
import { ItemClass, ItemSubClass, ItemSparse, JournalEncounter, JournalEncounterItem, JournalInstance, ItemBlock } from "./types";

export function isItemClass(obj: unknown): obj is ItemClass {
    const typedObj = obj as ItemClass
    return (
        (typedObj !== null &&
            typeof typedObj === "object" ||
            typeof typedObj === "function") &&
        typeof typedObj["ID"] === "string" &&
        typeof typedObj["ClassName_lang"] === "string" &&
        typeof typedObj["ClassID"] === "string" &&
        typeof typedObj["PriceModifier"] === "string" &&
        typeof typedObj["Flags"] === "string"
    )
}

export function isItemSubClass(obj: unknown): obj is ItemSubClass {
    const typedObj = obj as ItemSubClass
    return (
        (typedObj !== null &&
            typeof typedObj === "object" ||
            typeof typedObj === "function") &&
        typeof typedObj["DisplayName_lang"] === "string" &&
        typeof typedObj["VerboseName_lang"] === "string" &&
        typeof typedObj["ID"] === "string" &&
        typeof typedObj["ClassID"] === "string" &&
        typeof typedObj["SubClassID"] === "string" &&
        typeof typedObj["AuctionHouseSortOrder"] === "string" &&
        typeof typedObj["PrerequisiteProficiency"] === "string" &&
        typeof typedObj["Flags"] === "string" &&
        typeof typedObj["DisplayFlags"] === "string" &&
        typeof typedObj["WeaponSwingSize"] === "string" &&
        typeof typedObj["PostrequisiteProficiency"] === "string"
    )
}

export function isItemSparse(obj: unknown): obj is ItemSparse {
    const typedObj = obj as ItemSparse
    return (
        (typedObj !== null &&
            typeof typedObj === "object" ||
            typeof typedObj === "function") &&
        typeof typedObj["ID"] === "string" &&
        typeof typedObj["AllowableRace"] === "string" &&
        typeof typedObj["Description_lang"] === "string" &&
        typeof typedObj["Display3_lang"] === "string" &&
        typeof typedObj["Display2_lang"] === "string" &&
        typeof typedObj["Display1_lang"] === "string" &&
        typeof typedObj["Display_lang"] === "string" &&
        typeof typedObj["ExpansionID"] === "string" &&
        typeof typedObj["DmgVariance"] === "string" &&
        typeof typedObj["LimitCategory"] === "string" &&
        typeof typedObj["DurationInInventory"] === "string" &&
        typeof typedObj["QualityModifier"] === "string" &&
        typeof typedObj["BagFamily"] === "string" &&
        typeof typedObj["StartQuestID"] === "string" &&
        typeof typedObj["LanguageID"] === "string" &&
        typeof typedObj["ItemRange"] === "string" &&
        typeof typedObj["StatPercentageOfSocket_0"] === "string" &&
        typeof typedObj["StatPercentageOfSocket_1"] === "string" &&
        typeof typedObj["StatPercentageOfSocket_2"] === "string" &&
        typeof typedObj["StatPercentageOfSocket_3"] === "string" &&
        typeof typedObj["StatPercentageOfSocket_4"] === "string" &&
        typeof typedObj["StatPercentageOfSocket_5"] === "string" &&
        typeof typedObj["StatPercentageOfSocket_6"] === "string" &&
        typeof typedObj["StatPercentageOfSocket_7"] === "string" &&
        typeof typedObj["StatPercentageOfSocket_8"] === "string" &&
        typeof typedObj["StatPercentageOfSocket_9"] === "string" &&
        typeof typedObj["StatPercentEditor_0"] === "string" &&
        typeof typedObj["StatPercentEditor_1"] === "string" &&
        typeof typedObj["StatPercentEditor_2"] === "string" &&
        typeof typedObj["StatPercentEditor_3"] === "string" &&
        typeof typedObj["StatPercentEditor_4"] === "string" &&
        typeof typedObj["StatPercentEditor_5"] === "string" &&
        typeof typedObj["StatPercentEditor_6"] === "string" &&
        typeof typedObj["StatPercentEditor_7"] === "string" &&
        typeof typedObj["StatPercentEditor_8"] === "string" &&
        typeof typedObj["StatPercentEditor_9"] === "string" &&
        typeof typedObj["Stackable"] === "string" &&
        typeof typedObj["MaxCount"] === "string" &&
        typeof typedObj["MinReputation"] === "string" &&
        typeof typedObj["RequiredAbility"] === "string" &&
        typeof typedObj["SellPrice"] === "string" &&
        typeof typedObj["BuyPrice"] === "string" &&
        typeof typedObj["VendorStackCount"] === "string" &&
        typeof typedObj["PriceVariance"] === "string" &&
        typeof typedObj["PriceRandomValue"] === "string" &&
        typeof typedObj["Flags_0"] === "string" &&
        typeof typedObj["Flags_1"] === "string" &&
        typeof typedObj["Flags_2"] === "string" &&
        typeof typedObj["Flags_3"] === "string" &&
        typeof typedObj["OppositeFactionItemID"] === "string" &&
        typeof typedObj["ModifiedCraftingReagentItemID"] === "string" &&
        typeof typedObj["ContentTuningID"] === "string" &&
        typeof typedObj["PlayerLevelToItemLevelCurveID"] === "string" &&
        typeof typedObj["ItemNameDescriptionID"] === "string" &&
        typeof typedObj["RequiredTransmogHoliday"] === "string" &&
        typeof typedObj["RequiredHoliday"] === "string" &&
        typeof typedObj["Gem_properties"] === "string" &&
        typeof typedObj["Socket_match_enchantment_ID"] === "string" &&
        typeof typedObj["TotemCategoryID"] === "string" &&
        typeof typedObj["InstanceBound"] === "string" &&
        typeof typedObj["ZoneBound_0"] === "string" &&
        typeof typedObj["ZoneBound_1"] === "string" &&
        typeof typedObj["ItemSet"] === "string" &&
        typeof typedObj["LockID"] === "string" &&
        typeof typedObj["PageID"] === "string" &&
        typeof typedObj["ItemDelay"] === "string" &&
        typeof typedObj["MinFactionID"] === "string" &&
        typeof typedObj["RequiredSkillRank"] === "string" &&
        typeof typedObj["RequiredSkill"] === "string" &&
        typeof typedObj["ItemLevel"] === "string" &&
        typeof typedObj["AllowableClass"] === "string" &&
        typeof typedObj["ArtifactID"] === "string" &&
        typeof typedObj["SpellWeight"] === "string" &&
        typeof typedObj["SpellWeightCategory"] === "string" &&
        typeof typedObj["SocketType_0"] === "string" &&
        typeof typedObj["SocketType_1"] === "string" &&
        typeof typedObj["SocketType_2"] === "string" &&
        typeof typedObj["SheatheType"] === "string" &&
        typeof typedObj["Material"] === "string" &&
        typeof typedObj["PageMaterialID"] === "string" &&
        typeof typedObj["Bonding"] === "string" &&
        typeof typedObj["DamageType"] === "string" &&
        typeof typedObj["StatModifier_bonusStat_0"] === "string" &&
        typeof typedObj["StatModifier_bonusStat_1"] === "string" &&
        typeof typedObj["StatModifier_bonusStat_2"] === "string" &&
        typeof typedObj["StatModifier_bonusStat_3"] === "string" &&
        typeof typedObj["StatModifier_bonusStat_4"] === "string" &&
        typeof typedObj["StatModifier_bonusStat_5"] === "string" &&
        typeof typedObj["StatModifier_bonusStat_6"] === "string" &&
        typeof typedObj["StatModifier_bonusStat_7"] === "string" &&
        typeof typedObj["StatModifier_bonusStat_8"] === "string" &&
        typeof typedObj["StatModifier_bonusStat_9"] === "string" &&
        typeof typedObj["ContainerSlots"] === "string" &&
        typeof typedObj["RequiredPVPMedal"] === "string" &&
        typeof typedObj["RequiredPVPRank"] === "string" &&
        typeof typedObj["RequiredLevel"] === "string" &&
        typeof typedObj["InventoryType"] === "string" &&
        typeof typedObj["OverallQualityID"] === "string"
    )
}

export function isJournalEncounter(obj: unknown): obj is JournalEncounter {
    const typedObj = obj as JournalEncounter
    return (
        (typedObj !== null &&
            typeof typedObj === "object" ||
            typeof typedObj === "function") &&
        typeof typedObj["Name_lang"] === "string" &&
        typeof typedObj["Description_lang"] === "string" &&
        typeof typedObj["Map_0"] === "string" &&
        typeof typedObj["Map_1"] === "string" &&
        typeof typedObj["ID"] === "string" &&
        typeof typedObj["JournalInstanceID"] === "string" &&
        typeof typedObj["DungeonEncounterID"] === "string" &&
        typeof typedObj["OrderIndex"] === "string" &&
        typeof typedObj["FirstSectionID"] === "string" &&
        typeof typedObj["UiMapID"] === "string" &&
        typeof typedObj["MapDisplayConditionID"] === "string" &&
        typeof typedObj["Flags"] === "string" &&
        typeof typedObj["DifficultyMask"] === "string"
    )
}

export function isJournalEncounterItem(obj: unknown): obj is JournalEncounterItem {
    const typedObj = obj as JournalEncounterItem
    return (
        (typedObj !== null &&
            typeof typedObj === "object" ||
            typeof typedObj === "function") &&
        typeof typedObj["ID"] === "string" &&
        typeof typedObj["JournalEncounterID"] === "string" &&
        typeof typedObj["ItemID"] === "string" &&
        typeof typedObj["FactionMask"] === "string" &&
        typeof typedObj["Flags"] === "string" &&
        typeof typedObj["DifficultyMask"] === "string"
    )
}

export function isJournalInstance(obj: unknown): obj is JournalInstance {
    const typedObj = obj as JournalInstance
    return (
        (typedObj !== null &&
            typeof typedObj === "object" ||
            typeof typedObj === "function") &&
        typeof typedObj["ID"] === "string" &&
        typeof typedObj["Name_lang"] === "string" &&
        typeof typedObj["Description_lang"] === "string" &&
        typeof typedObj["MapID"] === "string" &&
        typeof typedObj["BackgroundFileDataID"] === "string" &&
        typeof typedObj["ButtonFileDataID"] === "string" &&
        typeof typedObj["ButtonSmallFileDataID"] === "string" &&
        typeof typedObj["LoreFileDataID"] === "string" &&
        typeof typedObj["Flags"] === "string" &&
        typeof typedObj["AreaID"] === "string"
    )
}

export function isItemBlock(obj: unknown): obj is ItemBlock {
    const typedObj = obj as ItemBlock
    return (
        (typedObj !== null &&
            typeof typedObj === "object" ||
            typeof typedObj === "function") &&
        typeof typedObj["id"] === "string" &&
        typeof typedObj["name"] === "string" &&
        typeof typedObj["journalEncounterItemId"] === "string" &&
        typeof typedObj["journalEncounterId"] === "string" &&
        typeof typedObj["journalInstanceId"] === "string"
    )
}
