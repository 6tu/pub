
wwwroot=/home/www
test -d $wwwroot || mkdir -p $wwwroot
/bin/cp -arf /opt/lampp/var/mysql $wwwroot
/opt/lampp/bin/mysql -V > $wwwroot/mysql/ver.txt
cd $wwwroot
zip -rq mysql.zip mysql
rm -rf mysql
chown daemon:daemon mysql.zip


# -r 复制子目录和文件
# -f 覆盖目标文件而不给出提示
# -a 递归复制时保留源文件或目录的属性，其作用等效"-dpR"参数
# -v 显示执行过程
# -x 必须与cp指令执行时所处的文件系统相同，否则不复制，亦不处理位于其他分区的文件


