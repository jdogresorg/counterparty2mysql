counterparty2mysql
---
counterparty2mysql is a php script which populates a mysql database with counterparty data.

counterparty2mysql loads Counterparty data by requesting 'messages' data for a block from the Counterparty API, and then processing each message. The Counterparty 'messages' table holds a list of all of the insert and update actions performed on counterparty tables.

By default counterparty2mysql starts at the first block with a Counterparty transaction (mainnet=278319, testnet=310546) and parses data for all blocks between the starting block and the current block.

If no starting block is given, counterparty2mysql will try to resume parsing at the last successfully parsed block, or use the first block with a counterparty transaction.


Database Customizations
---
- Index all assets, addresses, transactions, and contracts
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

Database Information
---
**Counterparty tables** (populated via 'messages')
- [bets](sql/bets.sql)
- [bet_expirations](sql/bet_expirations.sql)
- [bet_match_expirations](sql/bet_match_expirations.sql)
- [bet_match_resolutions](sql/bet_match_resolutions.sql)
- [bet_matches](sql/bet_matches.sql)
- [broadcasts](sql/broadcasts.sql)
- [btcpays](sql/btcpays.sql)
- [burns](sql/burns.sql)
- [cancels](sql/cancels.sql)
- [credits](sql/credits.sql)
- [debits](sql/debits.sql)
- [destructions](sql/destructions.sql)
- [dividends](sql/dividends.sql)
- [issuances](sql/issuances.sql)
- [order_expirations](sql/order_expirations.sql)
- [order_match_expirations](sql/order_match_expirations.sql)
- [order_matches](sql/order_matches.sql)
- [orders](sql/orders.sql)
- [rps](sql/rps.sql)
- [rps_expirations](sql/rps_expirations.sql)
- [rps_match_expirations](sql/rps_match_expirations.sql)
- [rps_matches](sql/rps_matches.sql)
- [rpsresolves](sql/rpsresolves.sql)
- [sends](sql/sends.sql)

**EVM-related tables**
- [contracts](sql/contracts.sql)
- [executions](sql/executions.sql)
- [nonces](sql/nonces.sql)
- [storage](sql/storage.sql)

**Additional tables** (populated by counterparty2mysql):
- [addresses](sql/addresses.sql)
- [assets](sql/assets.sql)
- [balances](sql/balances.sql)
- [blocks](sql/blocks.sql)
- [contract_ids](sql/contract_ids.sql)
- [transactions](sql/transactions.sql)

Helpful? Donate BTC, XCP or any Counterparty asset to 1JDogZS6tQcSxwfxhv6XKKjcyicYA4Feev
`J-Dog <j-dog@j-dog.net>`

