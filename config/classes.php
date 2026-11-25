<?php

/*
| `id` is the ChrClasses ID; `ItemSparse.AllowableClass` uses bit (id - 1).
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
        'id' => 1, 'name' => 'Warrior', 'armor' => $plate,
        'specs' => [
            'arms' => ['name' => 'Arms', 'primary' => 'str', 'weapons' => $twoHanders],
            'fury' => ['name' => 'Fury', 'primary' => 'str', 'weapons' => [$axe, $mace, $sword, $fist, $dagger, ...$twoHanders]],
            'protection' => ['name' => 'Protection', 'primary' => 'str', 'weapons' => [$axe, $mace, $sword, $fist, $dagger], 'shield' => true],
        ],
    ],
    'paladin' => [
        'id' => 2, 'name' => 'Paladin', 'armor' => $plate,
        'specs' => [
            'holy' => ['name' => 'Holy', 'primary' => 'int', 'weapons' => [$axe, $mace, $sword, ...$twoHanders], 'shield' => true, 'off_hand' => true],
            'protection' => ['name' => 'Protection', 'primary' => 'str', 'weapons' => [$axe, $mace, $sword], 'shield' => true],
            'retribution' => ['name' => 'Retribution', 'primary' => 'str', 'weapons' => $twoHanders],
        ],
    ],
    'hunter' => [
        'id' => 3, 'name' => 'Hunter', 'armor' => $mail,
        'specs' => [
            'beast-mastery' => ['name' => 'Beast Mastery', 'primary' => 'agi', 'weapons' => [$bow, $gun, $crossbow]],
            'marksmanship' => ['name' => 'Marksmanship', 'primary' => 'agi', 'weapons' => [$bow, $gun, $crossbow]],
            'survival' => ['name' => 'Survival', 'primary' => 'agi', 'weapons' => [$axe2h, $sword2h, $polearm, $staff]],
        ],
    ],
    'rogue' => [
        'id' => 4, 'name' => 'Rogue', 'armor' => $leather,
        'specs' => [
            'assassination' => ['name' => 'Assassination', 'primary' => 'agi', 'weapons' => [$axe, $mace, $sword, $fist, $dagger]],
            'outlaw' => ['name' => 'Outlaw', 'primary' => 'agi', 'weapons' => [$axe, $mace, $sword, $fist, $dagger]],
            'subtlety' => ['name' => 'Subtlety', 'primary' => 'agi', 'weapons' => [$axe, $mace, $sword, $fist, $dagger]],
        ],
    ],
    'priest' => [
        'id' => 5, 'name' => 'Priest', 'armor' => $cloth,
        'specs' => [
            'discipline' => ['name' => 'Discipline', 'primary' => 'int', 'weapons' => [$mace, $dagger, $staff, $wand], 'off_hand' => true],
            'holy' => ['name' => 'Holy', 'primary' => 'int', 'weapons' => [$mace, $dagger, $staff, $wand], 'off_hand' => true],
            'shadow' => ['name' => 'Shadow', 'primary' => 'int', 'weapons' => [$mace, $dagger, $staff, $wand], 'off_hand' => true],
        ],
    ],
    'death-knight' => [
        'id' => 6, 'name' => 'Death Knight', 'armor' => $plate,
        'specs' => [
            'blood' => ['name' => 'Blood', 'primary' => 'str', 'weapons' => $twoHanders],
            'frost' => ['name' => 'Frost', 'primary' => 'str', 'weapons' => [$axe, $mace, $sword, ...$twoHanders]],
            'unholy' => ['name' => 'Unholy', 'primary' => 'str', 'weapons' => $twoHanders],
        ],
    ],
    'shaman' => [
        'id' => 7, 'name' => 'Shaman', 'armor' => $mail,
        'specs' => [
            'elemental' => ['name' => 'Elemental', 'primary' => 'int', 'weapons' => [$axe, $mace, $fist, $dagger, $staff, $axe2h, $mace2h], 'shield' => true, 'off_hand' => true],
            'enhancement' => ['name' => 'Enhancement', 'primary' => 'agi', 'weapons' => [$axe, $mace, $fist]],
            'restoration' => ['name' => 'Restoration', 'primary' => 'int', 'weapons' => [$axe, $mace, $fist, $dagger, $staff, $axe2h, $mace2h], 'shield' => true, 'off_hand' => true],
        ],
    ],
    'mage' => [
        'id' => 8, 'name' => 'Mage', 'armor' => $cloth,
        'specs' => [
            'arcane' => ['name' => 'Arcane', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
            'fire' => ['name' => 'Fire', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
            'frost' => ['name' => 'Frost', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
        ],
    ],
    'warlock' => [
        'id' => 9, 'name' => 'Warlock', 'armor' => $cloth,
        'specs' => [
            'affliction' => ['name' => 'Affliction', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
            'demonology' => ['name' => 'Demonology', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
            'destruction' => ['name' => 'Destruction', 'primary' => 'int', 'weapons' => [$sword, $dagger, $staff, $wand], 'off_hand' => true],
        ],
    ],
    'monk' => [
        'id' => 10, 'name' => 'Monk', 'armor' => $leather,
        'specs' => [
            'brewmaster' => ['name' => 'Brewmaster', 'primary' => 'agi', 'weapons' => [$axe, $mace, $sword, $fist, $polearm, $staff]],
            'mistweaver' => ['name' => 'Mistweaver', 'primary' => 'int', 'weapons' => [$axe, $mace, $sword, $fist, $polearm, $staff], 'off_hand' => true],
            'windwalker' => ['name' => 'Windwalker', 'primary' => 'agi', 'weapons' => [$axe, $mace, $sword, $fist, $polearm, $staff]],
        ],
    ],
    'druid' => [
        'id' => 11, 'name' => 'Druid', 'armor' => $leather,
        'specs' => [
            'balance' => ['name' => 'Balance', 'primary' => 'int', 'weapons' => [$mace, $fist, $dagger, $mace2h, $polearm, $staff], 'off_hand' => true],
            'feral' => ['name' => 'Feral', 'primary' => 'agi', 'weapons' => [$mace, $fist, $dagger, $mace2h, $polearm, $staff]],
            'guardian' => ['name' => 'Guardian', 'primary' => 'agi', 'weapons' => [$mace, $fist, $dagger, $mace2h, $polearm, $staff]],
            'restoration' => ['name' => 'Restoration', 'primary' => 'int', 'weapons' => [$mace, $fist, $dagger, $mace2h, $polearm, $staff], 'off_hand' => true],
        ],
    ],
    'demon-hunter' => [
        'id' => 12, 'name' => 'Demon Hunter', 'armor' => $leather,
        'specs' => [
            'havoc' => ['name' => 'Havoc', 'primary' => 'agi', 'weapons' => [$warglaive, $axe, $sword, $fist]],
            'vengeance' => ['name' => 'Vengeance', 'primary' => 'agi', 'weapons' => [$warglaive, $axe, $sword, $fist]],
            'devourer' => ['name' => 'Devourer', 'primary' => 'int', 'weapons' => [$warglaive, $axe, $sword, $fist]],
        ],
    ],
    'evoker' => [
        'id' => 13, 'name' => 'Evoker', 'armor' => $mail,
        'specs' => [
            'devastation' => ['name' => 'Devastation', 'primary' => 'int', 'weapons' => [$axe, $mace, $sword, $fist, $dagger, $axe2h, $mace2h, $sword2h, $staff], 'off_hand' => true],
            'preservation' => ['name' => 'Preservation', 'primary' => 'int', 'weapons' => [$axe, $mace, $sword, $fist, $dagger, $axe2h, $mace2h, $sword2h, $staff], 'off_hand' => true],
            'augmentation' => ['name' => 'Augmentation', 'primary' => 'int', 'weapons' => [$axe, $mace, $sword, $fist, $dagger, $axe2h, $mace2h, $sword2h, $staff], 'off_hand' => true],
        ],
    ],
];
