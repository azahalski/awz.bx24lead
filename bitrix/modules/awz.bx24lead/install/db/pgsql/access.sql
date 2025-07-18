CREATE TABLE awz_bx24lead_role (
    ID int GENERATED BY DEFAULT AS IDENTITY NOT NULL,
    NAME varchar(250) NOT NULL,
    PRIMARY KEY (ID)
    );

CREATE TABLE awz_bx24lead_role_relation (
    ID int GENERATED BY DEFAULT AS IDENTITY NOT NULL,
    ROLE_ID int NOT NULL DEFAULT 0,
    RELATION varchar(8) NOT NULL DEFAULT '',
    PRIMARY KEY (ID)
    );

CREATE TABLE awz_bx24lead_permission (
    ID int GENERATED BY DEFAULT AS IDENTITY NOT NULL,
    ROLE_ID int NOT NULL DEFAULT 0,
    PERMISSION_ID varchar(32) NOT NULL DEFAULT '0',
    VALUE int NOT NULL DEFAULT 0,
    PRIMARY KEY (ID)
    );
CREATE INDEX awz_bx24lead_role_relation_role_id ON awz_bx24lead_role_relation (role_id);
CREATE INDEX awz_bx24lead_role_relation_relation ON awz_bx24lead_role_relation (relation);
CREATE INDEX awz_bx24lead_permission_role_id ON awz_bx24lead_permission (role_id);
CREATE INDEX awz_bx24lead_permission_permission_id ON awz_bx24lead_permission (permission_id);