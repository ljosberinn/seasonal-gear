for reference: https://docs.google.com/spreadsheets/d/e/2PACX-1vTmvLXqEpIbHUtn1J2rknIrK9K_yn-0NXKD-dsNhnt_afT75VQDr0BudTSpA_LyrX-DCvXRxfMsR84M/pubhtml#

```js
[
    raid: {
        meta: {
            name: 'Aberrus, the Shadowed Crucible',
            sources: {
                1111: 'Rashok, the Elder', // key encounter id, value name
            }
        },
        items: [
            {
                id: 1, 
                source: 1111, // encounter id
                slot: 1, // refers to some map
                armorType: 1, // refers to another map
                /* classIds: [] */  // optionally for possible restrictions. only present if it has restrictions like evoker diurna staff or class trinkets
            },
        ]
    },
    dungeons: {
        meta: 'Mythic+ Season 2',
        sources: {
            1234: 'Brackenhide Hollow', // key dungeon encounter id, just pick wcl. value name
        },
        items: [] // same as above
    },
    professions: {
        meta: 'Crafted Items',
        sources: {
            1: 'Blacksmithing', // key profession id, value name
        },
        items: [] // same as above
    }
]
```
