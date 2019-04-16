drop table IF EXISTS cards;
drop table IF EXISTS customers;
drop table IF EXISTS users;
drop table IF EXISTS settings;
drop table IF EXISTS rules;
drop table IF EXISTS holidays;
drop table IF EXISTS operations;

create table IF NOT EXISTS users
(
    name       varchar(255) not null primary key,
    password   varchar(255) not null,
    is_manager bool
) DEFAULT CHARACTER SET = 'utf8';

create table IF NOT EXISTS cards
(
    id       BIGINT unsigned auto_increment primary key,
    status   varchar(255),
    balance  DECIMAL(10, 2) null,
    discount DECIMAL(10, 2) null,
    turnover DECIMAL        not null default 0
) DEFAULT CHARACTER SET = 'utf8';

create table IF NOT EXISTS customers
(
    id         BIGINT unsigned auto_increment primary key,
    card_id    BIGINT unsigned,
    name       varchar(255)           not null,
    phone      BIGINT unsigned unique null,
    gender     char(1),
    birthDay   TINYINT Unsigned       null,
    birthMonth TINYINT Unsigned       null,
    birthYear  smallint Unsigned      null,
    foreign key (card_id) references cards (id)
        ON DELETE RESTRICT
) DEFAULT CHARACTER SET = 'utf8';

create table IF NOT EXISTS settings
(
    id          tinyint unsigned auto_increment primary key,
    bonuses     bool default true,
    apply_rules bool default true
) DEFAULT CHARACTER SET = 'utf8';

create table IF NOT EXISTS rules
(
    id              tinyint unsigned auto_increment primary key,
    type            varchar(255)   not null,
    condition_value decimal(10, 2) null,
    bonus           decimal(10, 2),
    multiplier      decimal(10, 2),
    percentage      decimal(10, 3),
    discount        decimal(10, 3)
) DEFAULT CHARACTER SET = 'utf8';

create table IF NOT EXISTS operations
(
    time      timestamp DEFAULT CURRENT_TIMESTAMP primary key,
    user_name varchar(255),
    type      varchar(10),
    message   varchar(255),
    value     DECIMAL(10, 2) null,
    foreign key (user_name) references users (name)
        ON DELETE SET NULL

) DEFAULT CHARACTER SET = 'utf8';

create table IF NOT EXISTS holidays
(
    date date not null primary key,
    name varchar(255)
) DEFAULT CHARACTER SET = 'utf8';
