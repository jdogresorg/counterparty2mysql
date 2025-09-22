-- v11.0.0 updates

-- fairminters table
ALTER TABLE fairminters ADD max_mint_per_address VARCHAR(250);
ALTER TABLE fairminters ADD mime_type VARCHAR(250) DEFAULT "text/plain";
ALTER TABLE fairminters MODIFY quantity_by_price VARCHAR(250);

-- issuances table
ALTER TABLE issuances ADD mime_type VARCHAR(250) DEFAULT "text/plain";

-- broadcasts table
ALTER TABLE broadcasts ADD mime_type VARCHAR(250) DEFAULT "text/plain";
