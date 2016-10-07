echo "--------------------------------------------------------------------------------------"
echo "--------------------------------------------------------------------------------------"
echo "Iniciando rotina $1 :: $2" 
date +'%d/%m/%Y %H:%M:%S'
echo "--------------------------------------------------------------------------------------"

while [ true ]; do
    echo "++++ Nova interacao..."
    date +'%d/%m/%Y %H:%M:%S'
    /usr/local/zend/bin/php -c /usr/local/zend/etc/php.ini -f /home/wms2/scripts/cron.php $1 $2
    date +'%d/%m/%Y %H:%M:%S'
    echo "++++ Interacao encerrada! (sleep) "
    sleep 1
    echo ""
    echo ""
done
