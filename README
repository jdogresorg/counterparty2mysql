counterparty2mysql
---
counterparty2mysql is a php script which populates a mysql database with counterparty data.

counterparty2mysql loads Counterparty data by requesting 'messages' data for a block from the Counterparty API, and then processing each message. The Counterparty 'messages' table holds a list of all of the insert and update actions performed on counterparty tables.

By default counterparty2mysql starts at the first block with a Counterparty transaction (block #278319) and parses data for all blocks between the starting block and the current block.

If no starting block is given, counterparty2mysql will try to resume parsing at the last successfully parsed block, or use the first Counterparty block # 278319.


Database Customizations
---
- Index all assets, addresses, and transaction hashes
- add row_index field to all counterparty tables
- create blocks table and index transaction hashes
- create assets table with up to date summary information
- create balances table to track address/asset balance information

Setup
---
```cd counterparty2mysql/
echo "CREATE DATABASE IF NOT EXISTS Counterparty" | mysql
echo "CREATE DATABASE IF NOT EXISTS Counterparty_Testnet" | mysql
cat sql/*.sql | mysql Counterparty
cat sql/*.sql | mysql Counterparty_Testnet
```

Command line arguments 
---
```
--testnet  Load testnet data
--block=#  Load data for given block
--single   Load single block
```

Helpful? Donate BTC, XCP or any Counterparty asset to 1JDogZS6tQcSxwfxhv6XKKjcyicYA4Feev

J-Dog <j-dog@j-dog.net>