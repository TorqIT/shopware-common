# Entity Export/Import for Shopware data

These commands can be used to export and then import shopware data based on a configuration json file.

`bin/console torq:entity-importer`
`bin/console torq:entity-exporter`

Both commands use default values for the config json and the data folder location but can be overridden by using the following options:

--configFile=CONFIGFILE  Config file for the export [default: "/var/www/html/custom/data/_config.json"]
--dataFolder=DATAFOLDER  Folder to export the data to [default: "/var/www/html/custom/data/"]

In the main project create a file in `src/custom/data` called _config.json with all the entities that need to be exported.  Any generated .json files will be placed in the same folder.

Sample _config.json:

```
[
    {
        "entity": "tag",
        "ids": [],
        "criteria":[],
        "associations": [
        ]
    },
    {
        "entity": "custom_field_set",
        "ids": [],
        "criteria":[],
        "associations": [
            "customFields",
            "relations"
        ]
    },
    {
        "entity": "category",
        "ids": [],
        "criteria":[
            {
                "type": "EqualsAny",
                "field": "name",
                "values": [
                    "Home"
                ]
            }
        ],
        "associations": [
            "customFields"
        ],
        "excludeFields": [
            "cmsPageId"
        ]
    },
    {
        "entity": "cms_page",
        "ids": [
           "0193e6491d48781d881174ec1349c050"
        ],
        "criteria":[
            {
                "field": "locked",
                "values": [
                    false
                ]
            }
        ],
        "associations": [
            "sections.blocks.slots",
            "customFields"
        ]
    }
]
```
