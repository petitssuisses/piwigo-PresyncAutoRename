PresyncAutoRename is a Piwigo plugin which automatically renames invalid files to piwigo compatible file names.
It is to be executed before importing files using the Synchronize feature

# Usage
PresyncAutoRename sanitizes your directories and files names within your gallery. It automatically corrects and renames files to a Piwigo compatible pattern.
All invalid characters are replaced with underscores so that the Synchronization process can run smoothly.
It has a simulation mode to preview which changes will be made on your files.
It automatically checks for files existence prior renaming them, appending a sequential number to them in case of duplicate

# Versions history
* Version 11.2
  * Added #1 - Automatic conversion of eastern arabic numbers to western characters (numbers) in filenames
* Version 11.1
  * Piwigo 11 compatible version
* Version 1.1.0
  * Small change in the admin panel (conditional display of the process results)
* Version 1.0.0
  * Initial version
  
# Todo list / Upcoming features
See issues list on GitHub : https://github.com/petitssuisses/piwigo-PresyncAutoRename/issues/

# Author 
Arnaud (petitssuisses) http://piwigo.org/forum/profile.php?id=19052