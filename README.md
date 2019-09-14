# Script package for downloading and parsing 'valgprotkoll'/'møtebok'

Scripts run using PHP. They run in sequence and outputs to file.

All PDFs are cached in this Git repo. So step 2 or step 3 does not require any download.

## Requirements:
- `php`
- `pdftotext` (step 1 / step 1.2 only)

Ubuntu:

`apt install php-cli poppler-utils`

## Commands

`php 1-valgprotokoll-download.php`

- Reads from urls.txt. Downloads PDFs. Read to txt ()

`php 1.2-valgprotokoll-elections-no.php`

- Reads PDFs in elections.no git repo. Manual git submodule update required.
- Git submodule update (NOTE! NOT SURE IF THIS IS THE RIGHT COMMAND): `git submodule update --remote elections-no.github.io`

`php 2-valgprotokoll-parser.php`

- Parses all txt files generated by step 1 / step 1.2. Outputs JSON.
- Will ignore any files with errors. Can be turned off with: `php 2-valgprotokoll-parser.php throw`

`php 3-valgprotokoll-html-report.php`
- Created HTML from JSON ouput in step 2.
