# PHPParser

These group of scripts are able to convert from a previous PHP version to the next one. The directory structure version/ indicate to what version the script will update the target source code. It is important to always go from one version to another, executing all scripts in the same version until go to the next directory of the next version.


## How to use
 - 

## How to code
- Import the lib/PHPParser.class.php in your script
- Create a PHPParser instance
- Get an array of all target files of the system
- Loop over each file
- Convert the current file into php tokens
- Analyze the tokens
- Register all changes that need to be done on file
- After analyze all files, print the changes to be done to see if it is ok
- Sort changes to be done by file and line ordered
- Execute the logic to read the target file and create the new one updated
