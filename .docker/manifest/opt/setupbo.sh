query="select count(*) from information_schema.tables where table_schema = database()"
count=`/usr/bin/mysql -hdatabase -u${DATABASE_USER} -p${DATABASE_PASSWORD} --skip-column-names ${DATABASE_NAME} << eof
$query
eof`
if [ $count -eq 0 ]
then
    mysql -hdatabase -u${DATABASE_USER} -p${DATABASE_PASSWORD} ${DATABASE_NAME} < /backoffice/tests/_data/ac66dump.sql
    mysql -hdatabase -u${DATABASE_USER} -p${DATABASE_PASSWORD} ${DATABASE_NAME} < /backoffice/tests/_data/create_ac_wallet_product.sql
fi
chmod 777 /backoffice/var -Rf