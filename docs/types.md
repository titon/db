# Column Types #

MySQL: http://dev.mysql.com/doc/refman/5.6/en/data-types.html
PostgreSQL: http://www.postgresql.org/docs/9.2/static/datatype.html
SQLite: http://www.sqlite.org/datatype3.html
MongoDB: http://docs.mongodb.org/manual/reference/operator/type/

The table below represents a good portion of popular data types.
The support for each driver is represented by a ~ or replacement data type.
The tilde ~ means that the specific type has the same name as the left column.
If a type has a different name, it is listed in the cell.
No value means the driver doesn't support that type.

```
				MySQL			PostgreSQL		SQLite

tinyint			~
smallint		~				~
mediumint		~
int				~				integer			integer
bigint			~				~

decimal			~				~
float			~				float4
double			~				float8			real
boolean			tinyint(1)		~

date			~				~
datetime		~				timestamp
time			~				~
year			year(4)			interval year

char			~				~
varchar			~				~				text

bit				~				~
binary			~				bytea
varbinary		~

tinyblob		~
mediumblob		~
blob			~				bytea			blob
longblob		~

tinytext		~
mediumtext		~
text			~				~
longtext		~
```

The table below represents the implemented Titon types on the left,
and the type used by each driver.

```
				MySQL			PostgreSQL		SQLite

smallint		smallint		smallint		integer
mediumint		mediumint		integer			integer
int				int				integer			integer
bigint			bigint			bigint			integer

decimal			decimal			decimal			real
float			float			float4			real
double			double			float8			real
boolean			tinyint(1)		boolean			integer

date			date			date			text
datetime		datetime		timestamp		text
time			time			time			text
year			year(4)			smallint		text

char			char			char			text
varchar			varchar			varchar			text

blob			blob			bytea			blob
text			text			text			text
```