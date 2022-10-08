--
-- PostgreSQL Table Schema (based on https://eventsauce.io/docs/message-storage/repository-table-schema/)
--
start transaction;
create table if not exists "your_table_name"
(
    "id"                bigserial               not null primary key,
    "event_id"          uuid                    not null,
    "aggregate_root_id" uuid                    not null,
    "version"           int check (version > 0) null,
    "payload"           json                    not null
);
create index "reconstitution" on "your_table_name" ("aggregate_root_id", "version" asc);
commit transaction;
