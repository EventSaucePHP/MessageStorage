#
# MySQL Table Schema (see https://eventsauce.io/docs/message-storage/repository-table-schema/)
#
create table if not exists `your_table_name`
(
    `id`                bigint unsigned  not null auto_increment,
    `event_id`          BINARY(16)       not null,
    `aggregate_root_id` BINARY(16)       not null,
    `version`           int(20) unsigned null,
    `payload`           varchar(16001)   not null,
    primary key (`id` asc),
    key `reconstitution` (`aggregate_root_id`, `version` asc)
) default character set utf8mb4
  collate utf8mb4_general_ci
  engine = InnoDB;
