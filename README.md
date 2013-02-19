fusion-table-php-api-and-shell
==============================

A Google Fusion Table PHP5 API + an ajax shell to use Fusion Table like a remote database.

*PHP Library*
The FTv1 class allow to perform all (?) the Fusion Tables commands, directly from you server. A complete example will be added shortly.

*FT Shell*
The Fusion Table Shell is a HTML shell thats allow to send the SQL commands directly on your Fusion Tables account, using it like a database server.

QUICKSTART:

- Go to https://code.google.com/apis/console/, In Services activate the Fusion Tables API and in API Access create a api key (Button "Create new server key..." at the bottom of the page).
- Try <code>SHOW TABLES</code>. It is show something? It works! See the Results tab and the Summary tab.
- Create a new table: <code>CREATE TABLE 'My first table' (name STRING, age NUMBER)</code>. Copy the returned encid from Summary tab.
- Look the structure of your new table: <code>DESCRIBE <encid> </code>
- Insert some data like usual... <code>INSERT INTO <encid> (name, age) VALUES ('John', '34')</code>
- Select the data <code>SELECT * FROM <encid> LIMIT 10 </code>
- Update the data <code>UPDATE <encid> SET age='35' WHERE name='John' </code>. You don't need to know first the Fusion Tables ROWID in this shell.
- Delete from your table <code>DELETE FORM <encid> WHERE name='John'</code>
- Drop your table: <code>DROP TABLE <encid></code>

The SQL documentation is already in the shell (tab SQL Reference).
			


