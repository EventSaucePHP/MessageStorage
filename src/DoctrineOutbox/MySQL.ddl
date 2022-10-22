#
# MySQL Table Schema (see https://eventsauce.io/docs/message-outbox/table-schema/)
#
create table if not exists `outbox_messages`
(
    `id`       bigint(20) unsigned not null auto_increment,
    `consumed` tinyint(1) unsigned not null default 0,
    `payload`  varchar(16001)      not null,
    primary key (`id`),
    key `is_consumed` (`consumed`, `id` asc)
) default character set utf8mb4
  collate utf8mb4_general_ci
  engine = InnoDB;
