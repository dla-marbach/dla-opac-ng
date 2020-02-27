--
-- Table structure for table 'tx_dlaopacng_tectonic'
--
CREATE TABLE tx_dlaopacng_tectonic (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    record_id varchar(100) DEFAULT '' NOT NULL,
    parent_id varchar(100) DEFAULT '' NOT NULL,
    listview_title text NOT NULL,
    listview_type text NOT NULL,
    listview_associate text NOT NULL,
    listview_additional1 text NOT NULL,
    listview_additional2 text NOT NULL,
    hasChild smallint(6) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY (parent_id),
    FULLTEXT (listview_title,listview_type,listview_associate,listview_additional1,listview_additional2)
);
