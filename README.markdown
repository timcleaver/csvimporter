# CSV Importer
Version: 0.0.1
Author: [Timothy William Cleaver]
Build Date: 2010-03-23
Requirements: Symphony 2.0.7

## Description
CSV Importer is a way of creating repeatable templates to import data from CSV files directly into Symphony sections. It provides a way of mapping content from CSV columns directly onto fields in your sections.

## Installation
1. Upload the 'csvimporter' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting "CSV Importer" on the System > Extensions page, choose Enable from the with-selected menu, then click Apply.
3. Use the extension from the Blueprints > CSV Importers menu.

## Creating an Importer (tutorial)
An Importer is similar to a Dynamic XML Datasource in its configuration. Let's create a fictitious importer to import a CSV file containing the users in your mailing lists. The CSV file has three columns:
    Person 1, email1@email.com, Interest1
    Person 2, email2@email.com, Interest2
    Person 3, email3@email.com, Interest1

Which will be importer into a symphony section called `Mailing Lists` containing three fields:
* User (Text Input)
* Email (Text Input)
* Interest (Textarea)

Note: The input file need not have the exact same number of columns as there are fields in the destination section. Each mapping is user definable as we will see below.


### Essentials
Start by creating a new Importer and give it a sensible **Name** such as `Mailing Lists` and add any notes into the **Description** field: `Import mailing list subscribers`.

### Source
This is where we define the CSV file. Start by uploading the CSV file from your local machine by clicking on the **File** `choose file` or `browse` button depending on your browser. You must then upload the file by clicking the `upload file` button. Once the file has been uploaded to symphony, you will be shown a snippet of how the file will be broken into columns by the importer. At this point you can select whether the CSV file has a leading line of column header information. To do so you can check the box that reads `This file contains an initial line of header information`. The example will change to reflect the status of the checkbox. The column headers will be used to select the columns to import but will otherwise be ignored by the importer. In our example file above there is no header information so we will leave this unchecked.

### Destination
Now we configure the values for each field in our new entry. Start by selecting the section into which we want to create new entries (`Mailing Lists`). We can now add mappings from a column in the file to import to the fields in the selected section. To do so click on the `Add item` link next to the `Mapping` dropdown. You will then be presented with a dropdown selection containing the columns in the file and a dropdown selection containing the fields of the selected section. If you have unchecked the `This file contains an initial line of header information` the column dropdown will contain Column 0, ..., Column (n - 1) for each numbered column in the file. Otherwise the column dropdown will contain the named columns as found in the first line of the file. Given our example file contains no header information we will be presented with the former. We create a mapping from the first column to the user field by selecting `Column 0` from the column dropdown and `User` from the field dropdown. We then click `Add item` and add the mappings from `Column 1` to `Email` and `Column 2` to `Interest` in the same way. If we check or uncheck the `This file contains an initial line of header information` checkbox at this point our mappings will be preserved and we may continue. However, if we change the section, any mappings we have defined will be lost and will have to be redefined.

At this point we can either run the importer as defined or save it as an importer template. Lets save the importer for later use.

## Run an Importer
There are two ways to run an importer. The first is to select the run button at the bottom of the `New CSV Importer`/`Edit CSV Importer` pages. This will run the importer on the uploaded template csv file. However, the template csv file is fixed once the importer has been saved. To run the importer on a file other than the template csv file it is necessary to use the csv importer index page found under `BluePrints` > `CSV Importers`. Each importer provides a file input in the `File` column of the index. Continuing with our example, one of the importers in the index will be the `Mailing List` importer. We select the file input in the `File` column of the `Mailing List` row and select the file from our local machine that we wish to import. Lets assume we are uploading the file `more.csv` with the following data:
    Person 4, email4@email.com, Interest2, 23, weekly
    Person 5, email4@email.com, Interest1, 18, daily
    Person 6, email4@email.com, Interest3, 13, immediate
    Person 7, email4@email.com, Interest1, 19, weekly
    Person 8, email4@email.com, Interest2, 25, immediate

Note that `more.csv` contains columns additional to those defined in the template csv file for this importer (user, email, interest). An age column and a digest column. The digest column indicates how the user wishes to receive the emails from the subscribed mailing list: as emails arrive (immediate), as a daily digest (daily) or a weekly digest (weekly). This will not pose an issue for the importer as columns that are not mapped to fields will simply be ignored.

We then use the `With Selected` drop down to select the `Run` option. We then click the `Apply` button. Symphony will then upload the file we have specified (more.csv) and run the importer over that file. If the file contains no information in a column that has been mapped to a field in the defined section, the value saved for that field will be `None`. Each row in the uploaded file will then be inserted as an entry into the section defined for the chosen importer. If an error was encountered during the import process, you will be notified by a status message at the top of the index page. You can see the results of the import by navigating to the destination section of the importer. In the case of our example, Content > Mailing Lists will now contain entries for Person 4 through Person 8.

## Delete an Importer
Once an importer is no longer useful it may be necessary to delete it. This can be achieved two ways. Firstly, an importer may be deleted via the edit page for that importer. To navigate to this page select Blueprints > CSV Importers and click the link of the name of the importer you wish to delete. You will then be presented with the details of the chosen importer. It can then be deleted by clicking on the delete button in the bottom right corner of the page. You will then be redirected to the index of importers. Secondly, importers can be deleted directly from the index page (Blueprints > CSV Importers). Select the importers you wish to delete and in the `With Selected` dropdown select delete. Deleting an importer will not effect any of the data previously imported via the importer.
