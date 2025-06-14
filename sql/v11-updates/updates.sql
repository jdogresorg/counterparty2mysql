-- v10.0.1 updates

-- fairminters table
ALTER TABLE fairminters ADD max_mint_per_address VARCHAR(250);
ALTER TABLE fairminters ADD mime_type VARCHAR(250) DEFAULT "text/plain";

-- issuances table
ALTER TABLE issuances ADD mime_type VARCHAR(250) DEFAULT "text/plain";
