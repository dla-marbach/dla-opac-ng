--
-- Table structure for table 'tx_dlaopacng_collection'
--
CREATE TABLE tx_dlaopacng_collection (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    record_id varchar(100) DEFAULT '' NOT NULL,
    parent_id varchar(100) DEFAULT '' NOT NULL,
    displayTree text NOT NULL,
    display text NOT NULL,
    displayName text NOT NULL,
    displayAddition1 text NOT NULL,
    displayAddition2 text NOT NULL,
    facet_value text NOT NULL,
    hasChild smallint(6) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (parent_id),
    KEY record_id (record_id),
    FULLTEXT search (displayTree,display,displayName,displayAddition1,displayAddition2)
);

--
-- Table structure for table 'tx_dlaopacng_classification'
--
CREATE TABLE tx_dlaopacng_classification (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    record_id varchar(100) DEFAULT '' NOT NULL,
    parent_id varchar(100) DEFAULT '' NOT NULL,
    displayTree text NOT NULL,
    display text NOT NULL,
    displayName text NOT NULL,
    displayAddition1 text NOT NULL,
    displayAddition2 text NOT NULL,
    facet_value text NOT NULL,
    hasChild smallint(6) DEFAULT '0' NOT NULL,
    count varchar(100) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (parent_id),
    KEY record_id (record_id),
    FULLTEXT search (displayTree,display,displayName,displayAddition1,displayAddition2)
);
