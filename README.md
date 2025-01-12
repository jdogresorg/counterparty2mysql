dogeparty2mysql
---
dogeparty2mysql is a php script which populates a mysql database with dogeparty data.

dogeparty2mysql loads Dogeparty data by requesting 'messages' data for a block from the Dogeparty API, and then processing each message. The Dogeparty 'messages' table holds a list of all of the insert and update actions performed on dogeparty tables.

By default dogeparty2mysql starts at the first block with a Dogeparty transaction (mainnet=335643 - testnet=166371) and parses data for all blocks between the starting block and the current block.

If no starting block is given, dogeparty2mysql will try to resume parsing at the last successfully parsed block, or use the first block with a dogeparty transaction.

Versions / Branches
---
- counterparty-classic - https://github.com/jdogresorg/counterparty2mysql/tree/counterparty-classic
- counterparty-2.0 - https://github.com/jdogresorg/counterparty2mysql/tree/counterparty-2.0
- dogeparty - https://github.com/jdogresorg/counterparty2mysql/tree/dogeparty
- unoparty - https://github.com/jdogresorg/counterparty2mysql/tree/unoparty

Database Customizations
---
- Index all assets, addresses, and transactions
- create assets table with up to date summary information
- create balances table to track address/asset balance information
- create blocks table and index transaction hashes
- create index_tx table to track tx_index/type information
- create dispenses table to track dispenser dispenses
- create markets table to track decentralized exchange (DEX) market info

Setup
---
```cd dogeparty2mysql/
echo "CREATE DATABASE IF NOT EXISTS Dogeparty" | mysql
echo "CREATE DATABASE IF NOT EXISTS Dogeparty_Testnet" | mysql
cat sql/*.sql | mysql Dogeparty
cat sql/*.sql | mysql Dogeparty_Testnet
```

Bootstrap Information
---
- [Dogeparty.sql.gz](bootstrap/Dogeparty.sql.gz) (Mainnet Block # 5,541,437)
- [Dogeparty_Testnet.sql.gz](bootstrap/Dogeparty_Testnet.sql.gz) (Testnet Block # 7,034,584)

Command line arguments 
---
```
--testnet    Load testnet data
--regtest    Load regtest data
--block=#    Load data for given block
--single     Load single block
--rollback=# Rollback data to a given block
--silent     Fail silently on insert errors
```

Database Information
---
**Dogeparty tables** (populated via 'messages')
- [addresses](sql/addresses.sql)
- [bets](sql/bets.sql)
- [bet_expirations](sql/bet_expirations.sql)
- [bet_match_expirations](sql/bet_match_expirations.sql)
- [bet_match_resolutions](sql/bet_match_resolutions.sql)
- [bet_matches](sql/bet_matches.sql)
- [broadcasts](sql/broadcasts.sql)
- [burns](sql/burns.sql)
- [cancels](sql/cancels.sql)
- [credits](sql/credits.sql)
- [debits](sql/debits.sql)
- [destructions](sql/destructions.sql)
- [dispensers](sql/dispensers.sql)
- [dispenses](sql/dispenses.sql)
- [dividends](sql/dividends.sql)
- [dogepays](sql/dogepays.sql)
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

**Additional tables** (populated by dogeparty2mysql):
- [assets](sql/assets.sql)
- [balances](sql/balances.sql)
- [blocks](sql/blocks.sql)
- [markets](sql/markets.sql)
- [index_addresses](sql/index_addresses.sql)
- [index_transactions](sql/index_transactions.sql)
- [index_tx](sql/index_tx.sql)
- [index_tx_types](sql/index_tx_types.sql)

Helpful? Donate DOGE, XDP or any Dogeparty asset to DJDogEP9xQ6cqPKiyAWLhxYrcU4oZih7L7