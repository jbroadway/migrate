create table #prefix#migrations (
	name char(128) not null primary key,
	revision char(32) not null,
	applied datetime not null
);

create index #prefix#migrations_applied on #prefix#migrations (applied);
