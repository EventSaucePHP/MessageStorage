--
-- PostgreSQL Table Schema (based on https://eventsauce.io/docs/message-outbox/table-schema/)
--
start transaction;
create table if not exists "outbox_messages"
(
    "id"       bigserial not null primary key,
    "consumed" bool      not null default false,
    "payload"  json      not null
);
create index "is_consumed" on "outbox_messages" ("consumed", "id" asc);
commit transaction;
