# Current-season loot from DB2 (wago.tools)

Research notes on how the client DB2 tables tie item drops to "current season" content.
Verified against live `12.1.5.70961` and PTR `12.1.7.71087` (identical results).

Source: `https://wago.tools/db2/{Table}/csv?build={version}`. Omitting `build` serves the
newest build (may be PTR); latest live version is at `https://wago.tools/api/builds/latest` → `.wow.version`.

## The chain

```
JournalTier (Expansion = 9000, "Current Season", ID 505)
  └─ JournalTierXInstance (JournalTierID = 505)
       ├─ JournalInstanceID ─► JournalInstance (MapID) ─► Map (InstanceType: 1 = dungeon, 2 = raid)
       └─ AvailabilityCondition ─► PlayerCondition.ModifierTreeID ─► ModifierTree children
              Type 289 (TimeEvent passed), Asset = TimeEvent ID
              Operator 2 (SingleTrue)  → season start TimeEvent
              Operator 3 (SingleFalse) → season end TimeEvent
JournalInstance ─► JournalEncounter (JournalInstanceID; DungeonEncounterID = encounter id from logs)
  └─ JournalEncounterItem (JournalEncounterID, ItemID, WorldStateExpressionID, DisplaySeasonID)
       ├─ JournalItemXDifficulty (JournalEncounterItemID → DifficultyID; no rows = all difficulties)
       └─ WorldStateExpressionID ─► WorldStateExpression.Expression (binary, see below)
```

Tier 505 holds several seasons at once; each row's `AvailabilityCondition` describes its
season window. On 12.1.5 the groups are:

| Condition | Start TE | End TE | MythicPlusSeason (by StartTimeEvent) | Instances |
|---|---|---|---|---|
| 149388 | 1525 | 1550 | 117 | Voidspire, March on Quel'Danas, Dreamrift, Sporefall |
| 156363 | 1552 | 2771 | 120 | Altar of Fangs, Den of Nalorakk, Murder Row, Blinding Vale, Voidscar Arena, Kings' Rest, Ruby Life Pools, Temple of Sethraliss, Tidebound Grotto, Venomous Abyss, Unbinding of Kith'ix, Keystone Dungeons (no loot) |
| 0 | – | – | – | Midnight (world bosses, always visible) |

## Leads verified

- Vile Alchemist's Band is item **268249** (268489 in the brief was a typo):
  JEI 47094 → JournalEncounter 2882 "Vashnik the Malignant" (DungeonEncounterID 3455)
  → JournalInstance 1320 The Venomous Abyss → JTXI 1612 (tier 505, cond 156363).
- Polished Lightwood Channeler 273778: JEI 47192 → JournalEncounter 2880 Zul'jan (DungeonEncounterID 3458)
  → JournalInstance 1322 Altar of Fangs → JTXI 1592 (tier 505, cond 156363).
- NPC ids (259181, 259447) aren't in any journal table; `JournalEncounterCreature` only
  holds CreatureDisplayInfoID. Link via `DungeonEncounterID` instead.

## Cross-checks that identify the same season

All of these point at "Midnight Season 2":

- TimeEvent **1552** = `MythicPlusSeason[120].StartTimeEvent` = start TE of cond 156363.
- `ChallengeModeItemBonusOverride.RequiredTimeEventPassed = 1552` (22 rows: keystone ilvl tables for the season).
- `MythicPlusSeasonTrackedMap.DisplaySeasonID = 37` → MapChallengeMode → `MapID` matches
  `JournalInstance.MapID` of exactly the 8 dungeons in cond 156363.
- `DisplaySeason[37]`: Season = 18 (global season counter, same numbering as the Blizzard
  M+ season API), text "Midnight Season 2".
- `JournalEncounterItem.DisplaySeasonID = 37` only on a few season-specific rows (Ula'tek trophies).
  It's sparse (76 of ~24k rows non-zero), so it isn't the primary link.

There's no direct FK between DisplaySeason and MythicPlusSeason / TimeEvent. The join is
"instances in the condition group" ∩ "maps tracked for the DisplaySeason".

## WorldStateExpression gating on loot rows

Some JEI rows are swapped depending on season. Most visible on legacy dungeons: Kings'
Rest has original BfA items (159xxx) plus remade versions (239xxx, plus a new 273649).

`Expression` is a hex blob. Layout as in TrinityCore `ConditionMgr::IsMeetingWorldStateExpression`:
`u8 enabled`, then RelOps joined by `u8 logic` (1 AND, 2 OR, 3 XOR, 0 end).
RelOp = Value `u8 cmp` (0 none, 1 ==, 2 !=, 3 <, 4 <=, 5 >, 6 >=) Value.
Value = Single `u8 arith` (0 none, else + - * / %) Single.
Single = `u8 type` (1 const i32, 2 worldstate u32, 3 function: `u32 fnId`, Single arg1, Single arg2).

Every JEI gate uses function **38** with a TimeEvent ID argument. Read it as "TimeEvent passed".
That's an inference: every argument is a known season start/end TE.

| WSE | Decoded | Used by |
|---|---|---|
| 52104 | TE(2771) == 0 | S2 legacy dungeon remakes (Kings' Rest 239xxx, Temple 239xxx, …) |
| 52105 | TE(2771) == 1 | original items come back after S2 ends |
| 51455 | TE(1867) == 1 AND TE(2771) == 0 | a few Venomous Abyss rows (mid-season unlock?) |
| 50899 | TE(1525) == 1 AND TE(1552) == 0 | S1 raids, S1-only rows |
| 50187/50188 | TE(1552) == 1 / == 0 | S1 legacy dungeons, before/after |
| 48764/48765, 44658/44659 | TE 600 / 574, 576 | TWW seasons |

## Item level and stats

Traced on `12.1.5.70961`. Reproduces all three reference items exactly:

| Item | Bonus IDs | ilvl | In-game | Computed |
|---|---|---|---|---|
| 268265 Aqirbane Reliquary (neck) | 6652:13668:13335:13848:13987 | 344 | 2466 Sta, 107 Crit/Haste/Mastery/Vers, 2 sockets | same |
| 268249 Vile Alchemist's Band (ring), Myth 6/6 | 6652:13668:13334:12854 | 334 | 2199 Sta, 73 Crit, 332 Mastery | same |
| 268249, Myth 5/6 | 6652:13668:13335:12853 | 331 | 2124 Sta, 72 Crit, 327 Mastery | same |

### What each bonus ID does

`ItemBonus` rows by `ParentItemBonusListID`. Types used here:

| Type | Meaning | Values |
|---|---|---|
| 0 | marker, no effect | – |
| 3 | quality | 4 = epic |
| 4 | name description | `ItemNameDescription` ID (2015 Heroic, 13145 Mythic, 14447 Venomcursed) |
| 6 | add sockets | V0 count, V1 socket type (7 = prismatic) |
| 7 | appearance modifier | – |
| 10 | repair cost multiplier | – |
| 23 | item effect | `ItemEffect` ID |
| 34 | upgrade track membership | V0 `ItemBonusListGroup` ID, V1 `SharedString` ID of the track name |
| 49 | **item level** | `ItemScalingConfig` ID, whose `ItemLevel` column is the absolute ilvl |

Applied to the examples:

- **6652**: marker only.
- **13668**: +1 prismatic socket. The neck already has `ItemSparse.SocketType_0 = 7`, so it ends up with 2. The ring has none natively, so it ends up with 1.
- **13332–13335**: name descriptions only ("Heroic", "Mythic"). They're picked by drop context and have no stat effect. That's why 13334 ("Heroic") can sit next to a Myth-track list in the 6/6 example.
- **12849–12856, 13848**: Myth track ranks. Each holds type 34 (group 618, track "Myth") and type 49 (the ilvl).
- **13987**: "Venomcursed" name, plus item effect 237622 → spell 1317582 "Venomcursed Ascendance". Only 268265 gets it, through its second `ItemXBonusTree` row (tree 6214).

`ItemSparse.ItemLevel` (219 for both items) is only the fallback when no type 49 bonus is present.

### Upgrade tracks

`ItemBonusListGroup` → `ItemBonusListGroupEntry` (ordered by `SequenceValue`) → bonus list → type 49 → `ItemScalingConfig.ItemLevel`.
All S2 groups have `PlayerConditionID = 143187` and `ItemGroupIlvlScalingID = 12`. S1 used groups 607–612 (condition 154598, scaling 11).

| Group | Track (SharedString) | 1 | 2 | 3 | 4 | 5 | 6 | 7* | 8* | 9* |
|---|---|---|---|---|---|---|---|---|---|---|
| 614 | Adventurer (971) | 266 | 269 | 272 | 276 | 279 | 282 | 285 | 289 | |
| 615 | Veteran (972) | 279 | 282 | 285 | 289 | 292 | 295 | 298 | 302 | |
| 616 | Champion (973) | 292 | 295 | 298 | 302 | 305 | 308 | 311 | 315 | |
| 617 | Hero (974) | 305 | 308 | 311 | 315 | 318 | 321 | 324 | 328 | |
| 618 | Myth (978) | 318 | 321 | 324 | 328 | 331 | 334 | 337 | 341 | 344 |

Myth bonus lists: 12849 … 12854 (ranks 1–6), 12855, 12856, 13848 (ranks 7–9).

- Ranks 2–6 carry an `ItemExtendedCostID`. That's the upgrade cost, e.g. 11477–11481 = 50g + 20 Myth Mistcrest (currency 3446). Rank 1 is the drop rank, so it has no cost.
- \*Ranks above 6 have `Flags = 3` (every other entry has 2) and no cost. You can't upgrade into them, and tooltips still say "x/6". So bit 0x1 is read as "above the normal cap". Nothing in the client DB2 points at these lists: no tree node, no override, no cost. The 344 neck gets there through the server-side Venomcursed mechanic. How 337/341 are reached isn't in DB2.
- `ItemGroupIlvlScalingEntry` for scaling 12 lists the five Mistcrest currencies (3442–3446) with `ConditionalCostScaling = 0.5` behind a PlayerCondition. That's the catch-up crest discount.
- Groups 626–630 also reference S2 track names, but they use the older type 48 (curve + pre-squish ilvl) and have no costs. They aren't on the drop path of either item, so ignore them.

### Which track a drop gets

`ItemXBonusTree` (item → tree) → `ItemBonusTreeNode` (recursive through `ChildItemBonusTreeID`). Pick nodes by `ItemContext` and, for keystones, by `MinMythicPlusLevel`/`MaxMythicPlusLevel`. A node yields a fixed `ChildItemBonusListID` or a `ChildItemBonusListGroupID` (track). Nodes without a context always apply; that's where 6652 and 13668 come from. A subtree can be limited to slots by `ItemBonusTree.InventoryTypeSlotMask` (bit n = InventoryType n): 13668 sits in tree 5837 with mask 2052 (neck, finger), so other slots on the same tree don't get the socket.

Venomous Abyss raid items (tree 5996 / 6108):

| ItemContext | Track group |
|---|---|
| 4 Raid Finder | 615 Veteran |
| 3 Raid Normal | 616 Champion |
| 5 Raid Heroic | 617 Hero |
| 6 Raid Mythic | 618 Myth |

S2 dungeon tree 5939 is swapped in for the S1 tree 5780 via `ChallengeModeItemBonusOverride` row 1126 (`RequiredTimeEventPassed = 1552`). In that tree:

| ItemContext | Condition | Track group |
|---|---|---|
| 1 Dungeon Normal | – | fixed list 13661 = 259 |
| 2 Dungeon Heroic | – | 614 Adventurer |
| 23 Dungeon Mythic | – | 616 Champion |
| 16 / 33 | key ≤ 5 | 616 Champion |
| 16 / 33 | key ≥ 6 | 617 Hero |
| 35 | key ≤ 9 | 617 Hero |
| 35 | key ≥ 10 | 618 Myth |

Other contexts (102, 103, 146–148, 162, 173–179, 189, 190, 14) map to tracks as well. They're presumably delves, world content and vault, but that's unconfirmed.

The **starting rank** inside the group comes from `IblGroupPointsModSetID` on the node. Values seen: 2962–2967, 2970, 4366, 4426–4428, 4629. Mythic raid varies by item: 4366 for the ring, 2970 for the neck. That table isn't in the client DB2 list on wago.tools, so the drop rank can't be derived from DB2. The rank after upgrades is always fully defined by the type 49 bonus in the link, so this only matters for predicting the drop ilvl.

### Stat formula

Inputs:

- `ItemSparse` per item: `StatModifier_bonusStat_i` (stat type), `StatPercentEditor_i` (allocation, 1/10000), `StatPercentageOfSocket_i`, `InventoryType`.
- `RandPropPoints[ilvl].EpicF_{slot}` is the budget. Use the float column. Epic, Superior and Good are identical at these ilvls. Slot columns per the simc convention: 0 head/chest/legs/2H, 1 shoulder/hands/waist/feet/trinket, 2 neck/finger/back/wrist/off-hand, 3 1H weapon. Columns 1 (trinket), 2 and 3 (dagger) are verified.
- Game tables (not DB2). Fetch them from CASC: `https://wago.tools/api/casc/{fdid}?version={build}`, tab-separated:
  - `gametables/staminamultbyilvl.txt` (fdid 1980632). Same Armor/Weapon/Trinket/Jewelry columns as the rating table.
  - `gametables/combatratingsmultbyilvl.txt` (fdid 1391670). Columns: Armor, Weapon, Trinket, Jewelry.
  - `gametables/itemsocketcostperlevel.txt` (fdid 1391643)

```
alloc   = sum of StatPercentEditor_i over all i with the same stat type
raw     = EpicF_slot[ilvl] * alloc / 10000
primary = round(raw)
stamina = round(raw * StaminaMultByILvl[ilvl][column])
rating  = round(raw * CombatRatingsMultByILvl[ilvl][column])   // Jewelry for neck/finger, else Armor/Weapon/Trinket
```

Rounding happens once, at the end. Two other orderings each miss at least one reference value:

- Round `raw` first: gives 2471 / 2198 / 2128 stamina.
- Floor instead of round: gives crit 72 / 71.

A stat type can appear more than once. Caster weapons carry two Int entries (e.g. dagger 251111: 5259 + 25370), and they must be summed before rounding: 67 + 325 = 392, but round(raw of 30629) = 393 matches in-game.

Verified against S1 items as well (S1 Myth 6/6 = 289):

| Item | ilvl | In-game | Computed |
|---|---|---|---|
| 249920 Eye of Midnight (ring) | 289 | 1309 Sta, 303 Haste, 2 sockets, effect | same |
| 249808 Litany of Lightblind Wrath (trinket) | 298 | 128 Int, on-use | same |
| 251111 Splitshroud Stinger (dagger) | 298 | 393 Int, 1290 Sta, 56 Crit, 30 Haste, no effect | same |

Unverified pieces:

- The socket penalty `StatPercentageOfSocket_i × ItemSocketCostPerLevel`. Both examples have 0.

Worked example, ring 268249 (Epic column 2, alloc Sta 7889, Mastery 14350, Crit 3150) at Myth 6/6 (334):

- `EpicF_2` = 201.690
- Sta = 159.113 × 13.8224 = **2199**
- Mastery = 289.426 × 1.14838 = **332**
- Crit = 63.532 × 1.14838 = **73**

The neck has the same stamina allocation (7889), so neck and ring share stamina at every ilvl.

Myth track, computed (ring Sta/Crit/Mastery, neck Crit; neck Haste/Mastery/Vers = neck Crit):

| ilvl | 318 | 321 | 324 | 328 | 331 | 334 | 337 | 341 | 344 |
|---|---|---|---|---|---|---|---|---|---|
| Sta | 1830 | 1895 | 1962 | 2053 | 2124 | 2199 | 2277 | 2382 | 2466 |
| Ring Crit | 66 | 68 | 69 | 71 | 72 | 73 | 74 | 76 | 77 |
| Ring Mastery | 303 | 308 | 314 | 321 | 327 | 332 | 338 | 345 | 351 |
| Neck secondary | 92 | 94 | 96 | 98 | 100 | 101 | 103 | 105 | 107 |

Per-ilvl inputs for every S2 step:

| ilvl | EpicF_0 | EpicF_1 | EpicF_2 | EpicF_3 | Sta mult | CR armor/weapon/trinket | CR jewelry |
|---|---|---|---|---|---|---|---|
| 266 | 190.283 | 142.712 | 107.034 | 95.141 | 11.7722 | 1.0954 | 1.3383 |
| 269 | 195.676 | 146.757 | 110.068 | 97.838 | 11.8662 | 1.0830 | 1.3368 |
| 272 | 201.223 | 150.917 | 113.188 | 100.612 | 11.8767 | 1.0703 | 1.3344 |
| 276 | 208.864 | 156.648 | 117.486 | 104.432 | 11.9843 | 1.0533 | 1.3298 |
| 279 | 214.785 | 161.089 | 120.817 | 107.392 | 12.1609 | 1.0404 | 1.3255 |
| 282 | 220.873 | 165.655 | 124.241 | 110.437 | 12.2913 | 1.0274 | 1.3203 |
| 285 | 227.134 | 170.351 | 127.763 | 113.567 | 12.3844 | 1.0143 | 1.3144 |
| 289 | 235.759 | 176.820 | 132.615 | 117.880 | 12.5072 | 0.9968 | 1.3055 |
| 292 | 242.442 | 181.832 | 136.374 | 121.221 | 12.5805 | 0.9837 | 1.2981 |
| 295 | 249.315 | 186.986 | 140.240 | 124.657 | 12.6703 | 0.9704 | 1.2902 |
| 298 | 256.382 | 192.287 | 144.215 | 128.191 | 12.7531 | 0.9572 | 1.2816 |
| 302 | 266.118 | 199.588 | 149.691 | 133.059 | 12.8573 | 0.9396 | 1.2695 |
| 305 | 273.661 | 205.246 | 153.934 | 136.831 | 12.9427 | 0.9263 | 1.2598 |
| 308 | 281.419 | 211.064 | 158.298 | 140.709 | 13.0426 | 0.9131 | 1.2497 |
| 311 | 289.396 | 217.047 | 162.785 | 144.698 | 13.1499 | 0.8999 | 1.2392 |
| 315 | 300.385 | 225.289 | 168.967 | 150.193 | 13.2647 | 0.8824 | 1.2246 |
| 318 | 308.900 | 231.675 | 173.756 | 154.450 | 13.3512 | 0.8693 | 1.2133 |
| 321 | 317.656 | 238.242 | 178.682 | 158.828 | 13.4427 | 0.8562 | 1.2017 |
| 324 | 326.661 | 244.996 | 183.747 | 163.330 | 13.5321 | 0.8432 | 1.1898 |
| 328 | 339.065 | 254.299 | 190.724 | 169.533 | 13.6414 | 0.8260 | 1.1735 |
| 331 | 348.677 | 261.508 | 196.131 | 174.338 | 13.7293 | 0.8132 | 1.1611 |
| 334 | 358.561 | 268.920 | 201.690 | 179.280 | 13.8224 | 0.8004 | 1.1484 |
| 337 | 368.725 | 276.543 | 207.408 | 184.362 | 13.9135 | 0.7878 | 1.1355 |
| 341 | 382.726 | 287.045 | 215.283 | 191.363 | 14.0265 | 0.7710 | 1.1181 |
| 344 | 393.575 | 295.181 | 221.386 | 196.788 | 14.1212 | 0.7586 | 1.1049 |

The secondary multiplier falls as ilvl rises (jewelry 1.34 → 1.10, armor 1.10 → 0.76), while the stamina multiplier rises. So secondaries grow slower than the budget and stamina grows faster.

### Squish

Both items and every S2 `ItemScalingConfig` row have `ItemSquishEraID = 2` (`ItemSquishEra[2]`: patch 120000, curve 92181). `ItemOffsetCurveID = 47` → curve 88583 is the identity (0→0, 1300→1300) with offset 0. So `ItemScalingConfig.ItemLevel` is already the post-squish ilvl.

Curve 92181 maps pre-squish ilvls (e.g. 571 → 80, 730 → 170). It only matters for era-1 items.

## Caveats

1. **DB2 can't tell which season is active right now.** Season dates live on the server.
   `TimeEventData` exists but only has 5 rows for an unrelated event (901). The prototype
   picks the condition group with the highest start TE. That breaks when next season's rows
   ship to live before the season starts. Recommendation: pin the season in project config
   by start TimeEvent (or MythicPlusSeason ID) next to region start dates, and derive
   everything else from DB2.
2. **Evaluating WSE in a window** needs "has TE x passed?" for other TEs. The heuristic is
   `passed(x) = x < seasonEndTE`, assuming TimeEvent IDs grow over time. That holds for every
   ID seen (574 < 576 < 600 < 1525 < 1550 < 1552 < 1867 < 2771), but it isn't guaranteed.
3. **History isn't kept.** S1 *dungeons* were removed from tier 505. Only S1 raids remain,
   under the closed window. To reproduce a past season, extract from a build pinned to that
   season (the old Remix app pinned builds per season).
4. **Difficulty filtering matters.** Temple of Sethraliss has the old helm 159374 limited to
   Normal (1), and the current version 239035 on Heroic/Mythic (2, 23). The prototype keeps
   a row if it has no JIXD rows, or if its difficulties overlap {23} for dungeons or
   {14, 15, 16, 17} for raids. Timewalking is 24.
5. **Item filter:** `Item.ClassID` 2 (weapon) or 4 (armor), plus 15/0 tier tokens (e.g. Venom*
   Icon). JEI also lists mounts (15/5), housing decor (20), recipes (9).
6. `JournalEncounterItem.Flags` meaning is unconfirmed. Seen: 0x2 on legacy dungeon rows and
   some special drops, 0x4 on decor/recipes, 0x8 on very rare weapons.
   `FactionMask` is -1 everywhere checked.
7. Tier tokens → class set items isn't covered here. Old app used `ItemSet` IDs per season.

## Suggested PHP shape

Per build: download JournalTier, JournalTierXInstance, JournalInstance, JournalEncounter,
JournalEncounterItem, JournalItemXDifficulty, PlayerCondition, ModifierTree,
WorldStateExpression, MythicPlusSeason, Map, Item, ItemSparse. Optionally add
MythicPlusSeasonTrackedMap, MapChallengeMode and DisplaySeason for cross-checks.
For ilvl/stats, also download:

- DB2: ItemXBonusTree, ItemBonusTreeNode, ItemBonus, ItemBonusListGroup, ItemBonusListGroupEntry, ItemScalingConfig, RandPropPoints, ChallengeModeItemBonusOverride.
- Game tables from CASC: StaminaMultByILvl, CombatRatingsMultByILvl. Optional: ItemSocketCostPerLevel, SharedString, ItemExtendedCost.

1. Config: season slug → start TimeEvent (e.g. 1552), plus region dates.
2. Find tier with `Expansion = 9000`. Keep JTXI rows whose condition's ModifierTree has a
   Type 289 / Operator 2 child with Asset = configured start TE. Read the end TE from the
   Operator 3 sibling.
3. Instances → encounters → JEI. Filter by WSE (small decoder, ~30 lines), JIXD difficulty,
   and item class.
4. Optionally assert that the resulting dungeon maps equal MythicPlusSeasonTrackedMap for the
   matching DisplaySeason. This catches Blizzard reshuffling the tier.

A JS prototype of steps 2–3 lives in the session scratchpad (`resolve.cjs`, `wse.cjs`, `lib.cjs`).
