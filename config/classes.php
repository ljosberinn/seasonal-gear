<?php

/*
| `id` is the ChrClasses ID; `ItemSparse.AllowableClass` uses bit (id - 1).
| `color` is the in-game class color; `icon` names a zamimg.com icon file.
| Armor and weapon values are Item subclass IDs. `shield`/`off_hand` gate off-hand slot items.
*/

$cloth = 1;
$leather = 2;
$mail = 3;
$plate = 4;

[$axe, $axe2h, $bow, $gun, $mace, $mace2h, $polearm, $sword, $sword2h, $warglaive, $staff] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
[$fist, $dagger, $crossbow, $wand] = [13, 15, 18, 19];

$twoHanders = [$axe2h, $mace2h, $sword2h, $polearm];

return [
    'warrior' => [
        'id' => 1, 'name' => 'Warrior', 'color' => '#C69B6D', 'icon' => 'classicon_warrior', 'armor' => $plate,
        'specs' => [
            'arms' => ['name' => 'Arms', 'icon' => 'ability_warrior_savageblow', 'primary' => 'str', 'weapons' => $twoHanders],
            'fury' => ['name' => 'Fury', 'icon' => 'ability_warrior_innerrage', 'primary' => 'str', 'weapons' => [$axe, $mace, $sword, $fist, $dagger, ...$twoHanders]],
            'protection' => ['name' => 'Protection', 'icon' => 'ability_warrior_defensivestance', 'primary' => 'str', 'weapons' => [$axe, $mace, $sword, $fist, $dagger], 'shield' => true],
        ],
    ],
    'paladin' => [
        'id' => 2, 'name' => 'Paladin', 'color' => '#F48CBA', 'icon' => 'classicon_paladin', 'armor' => $plate,
        'specs' => [
            'holy' => ['name' => 'Holy', 'icon' => 'spell_holy_holybolt', 'primary' => 'int', 'weapons' => [$axe, $mace, $sword, ...$twoHanders], 'shield' => true, 'off_hand' => true],
            'protection' => ['name' => 'Protection', 'icon' => 'ability_paladin_shieldofthetemplar', 'primary' => 'str', 'weapons' => [$axe, $mace, $sword], 'shield' => true],
            'retribution' => ['name' => 'Retribution', 'icon' => 'spell_holy_auraoflight', 'primary' => 'str', 'weapons' => $twoHanders],
        ],
    ],
    'hunter' => [
        'id' => 3, 'name' => 'Hunter', 'color' => '#AAD372', 'icon' => 'classicon_hunter', 'armor' => $mail,
        'specs' => [
            'beast-mastery' => ['name' => 'Beast Mastery', 'icon' => 'ability_hunter_bestialdiscipline', 'primary' => 'agi', 'weapons' => [$bow, $gun, $crossbow]],
            'marksmanship' => ['name' => 'Marksmanship', 'icon' => 'ability_hunter_focusedaim', 'primary' => 'agi', 'weapons' => [$bow, $gun, $crossbow]],
            'survival' => ['name' => 'Survival', 'icon' => 'ability_hunter_camouflage', 'primary' => 'agi', 'weapons' => [$axe2h, $sword2h, $polearm, $staff]],
        ],
    ],
    'rogue' => [
        'id' => 4, 'name' => 'Rogue', 'color' => '#FFF468', 'icon' => 'classicon_rogue', 'armor' => $leather,
        'specs' => [
            'assassination' => ['name' => 'Assassination', 'icon' => 'ability_rogue_deadlybrew', 'primary' => 'agi', 'weapons' => [$axe, $mace, $sword, $fist, $dagger]],
            'outlaw' => ['name' => 'Outlaw', 'icon' => 'ability_rogue_waylay', 'primary' => 'agi', 'weapons' => [$axe, $mace, $sword, $fist, $dagger]],
            'subtlety' => ['name' => 'Subtlety', 'icon' => 'ability_stealth', 'primary' => 'agi', 'weapons' => [$axe, $mace, $sword, $fist, $dagger]],
        ],
    ],
    'priest' => [
        'id' => 5, 'name' => 'Priest', 'color' => '#FFFFFF', 'icon' => 'classicon_priest', 'armor' => $cloth,
        'specs' => [
            'discipline' => ['name' => 'Discipline', 'icon' => 'spell_holy_powerwordshield', 'primary' => 'int', 'weapons' => [$mace, $dagger, $staff, $wand], 'off_hand' => true],
            'holy' => ['name' => 'Holy', 'icon' => 'spell_holy_guardianspirit', 'primary' => 'int', 'weapons' => [$mace, $dagger, $staff, $wand], 'off_hand' => true],
            'shadow' => ['name' => 'Shadow', 'icon' => 'spell_shadow_shadowwordpain', 'primary' => 'int', 'weapons' => [$mace, $dagger, $staff, $wand], 'off_hand' => true],
        ],
    ],
    'death-knight' => [
        'id' => 6, 'name' => 'Death Knight', 'color' => '#C41E3A', 'icon' => 'spell_deathknight_classicon', 'armor' => $plate,
        'specs' => [
            'blood' => ['name' => 'Blood', 'icon' => 'spell_deathknight_bloodpresence', 'primary' => 'str', 'weapons' => $twoHanders],
            'frost' => ['name' => 'Frost', 'icon' => 'spell_deathknight_frostpresence', 'primary' => 'str', 'weapons' => [$axe, $mace, $sword, ...$twoHanders]],
            'unholy' => ['name' => 'Unholy', 'icon' => 'spell_deathknight_unholypresence', 'primary' => 'str', 'weapons' => $twoHanders],
        ],
    ],
    'shaman' => [
        'id' => 7, 'name' => 'Shaman', 'color' => '#0070DD', 'icon' => 'classicon_shaman', 'armor' => $mail,
        'specs' => [
            'elemental' => ['name' => 'Elemental', 'icon' => 'spell_nature_lightning', 'primary' => 'int', 'weapons' => [$axe, $mace, $fist, $dagger, $staff, $axe2h, $mace2h], 'shield' => true, 'off_hand' => true],
            'enhancement' => ['name' => 'Enhancement', 'icon' => 'spell_shaman_improvedstormstrike', 'primary' => 'agi', 'weapons' => [$axe, $mace, $fist]],
            'restoration' => ['name' => 'Restoration', 'icon' => 'spell_nature_magicimmunity', 'primary' => 'int', 'weapons' => [$axe, $mace, $fist, $dagger, $staff, $axe2h, $mace2h], 'shield' => true, 'off_hand' => true],
        ],
    ],
    'mage' => [
        'id' => 8, 'name' => 'Mage', 'color' => '#3FC7EB', 'icon' => 'classicon_mage', 'armor' => $cloth,
        'specs' => [
            'arcane' => ['name' => 'Arcane', 'icon' => 'spell_holy_magicalsentry', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
            'fire' => ['name' => 'Fire', 'icon' => 'spell_fire_firebolt02', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
            'frost' => ['name' => 'Frost', 'icon' => 'spell_frost_frostbolt02', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
        ],
    ],
    'warlock' => [
        'id' => 9, 'name' => 'Warlock', 'color' => '#8788EE', 'icon' => 'classicon_warlock', 'armor' => $cloth,
        'specs' => [
            'affliction' => ['name' => 'Affliction', 'icon' => 'spell_shadow_deathcoil', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
            'demonology' => ['name' => 'Demonology', 'icon' => 'spell_shadow_metamorphosis', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
            'destruction' => ['name' => 'Destruction', 'icon' => 'spell_shadow_rainoffire', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
        ],
    ],
    'monk' => [
        'id' => 10, 'name' => 'Monk', 'color' => '#00FF98', 'icon' => 'classicon_monk', 'armor' => $leather,
        'specs' => [
            'brewmaster' => ['name' => 'Brewmaster', 'icon' => 'spell_monk_brewmaster_spec', 'primary' => 'agi', 'weapons' => [$axe, $mace, $sword, $fist, $polearm, $staff]],
            'mistweaver' => ['name' => 'Mistweaver', 'icon' => 'spell_monk_mistweaver_spec', 'primary' => 'int', 'weapons' => [$axe, $mace, $sword, $fist, $polearm, $staff], 'off_hand' => true],
            'windwalker' => ['name' => 'Windwalker', 'icon' => 'spell_monk_windwalker_spec', 'primary' => 'agi', 'weapons' => [$axe, $mace, $sword, $fist, $polearm, $staff]],
        ],
    ],
    'druid' => [
        'id' => 11, 'name' => 'Druid', 'color' => '#FF7C0A', 'icon' => 'classicon_druid', 'armor' => $leather,
        'specs' => [
            'balance' => ['name' => 'Balance', 'icon' => 'spell_nature_starfall', 'primary' => 'int', 'weapons' => [$mace, $fist, $dagger, $mace2h, $polearm, $staff], 'off_hand' => true],
            'feral' => ['name' => 'Feral', 'icon' => 'ability_druid_catform', 'primary' => 'agi', 'weapons' => [$mace, $fist, $dagger, $mace2h, $polearm, $staff]],
            'guardian' => ['name' => 'Guardian', 'icon' => 'ability_racial_bearform', 'primary' => 'agi', 'weapons' => [$mace, $fist, $dagger, $mace2h, $polearm, $staff]],
            'restoration' => ['name' => 'Restoration', 'icon' => 'spell_nature_healingtouch', 'primary' => 'int', 'weapons' => [$mace, $fist, $dagger, $mace2h, $polearm, $staff], 'off_hand' => true],
        ],
    ],
    'demon-hunter' => [
        'id' => 12, 'name' => 'Demon Hunter', 'color' => '#A330C9', 'icon' => 'classicon_demonhunter', 'armor' => $leather,
        'specs' => [
            'havoc' => ['name' => 'Havoc', 'icon' => 'ability_demonhunter_specdps', 'primary' => 'agi', 'weapons' => [$warglaive, $axe, $sword, $fist]],
            'vengeance' => ['name' => 'Vengeance', 'icon' => 'ability_demonhunter_spectank', 'primary' => 'agi', 'weapons' => [$warglaive, $axe, $sword, $fist]],
            'devourer' => ['name' => 'Devourer', 'icon' => 'classicon_demonhunter_void', 'primary' => 'int', 'weapons' => [$warglaive, $axe, $sword, $fist]],
        ],
    ],
    'evoker' => [
        'id' => 13, 'name' => 'Evoker', 'color' => '#33937F', 'icon' => 'classicon_evoker', 'armor' => $mail,
        'specs' => [
            'devastation' => ['name' => 'Devastation', 'icon' => 'classicon_evoker_devastation', 'primary' => 'int', 'weapons' => [$axe, $mace, $sword, $fist, $dagger, $axe2h, $mace2h, $sword2h, $staff], 'off_hand' => true],
            'preservation' => ['name' => 'Preservation', 'icon' => 'classicon_evoker_preservation', 'primary' => 'int', 'weapons' => [$axe, $mace, $sword, $fist, $dagger, $axe2h, $mace2h, $sword2h, $staff], 'off_hand' => true],
            'augmentation' => ['name' => 'Augmentation', 'icon' => 'classicon_evoker_augmentation', 'primary' => 'int', 'weapons' => [$axe, $mace, $sword, $fist, $dagger, $axe2h, $mace2h, $sword2h, $staff], 'off_hand' => true],
        ],
    ],
];
