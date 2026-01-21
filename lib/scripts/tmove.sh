
if [ ! -d vendor ]; then 
  echo "Must be in a tsugi folder"
  exit
fi

if [ -d ../../tsugi-php ]; then 
  x="../../tsugi-php"
elif [ -d ../tsugi-php ]; then 
  x="../tsugi-php"
else
  echo Could not find tsugi-php in `pwd`
  exit
fi

count=0
for i in `git status vendor | egrep 'modified:|new file' | sed 's/.*modified: *//' | sed 's/.*new file: *//'`;
do
    j=`echo $i | sed "s'vendor/tsugi/lib'../../tsugi-php'"`
    j=`echo $i | sed "s'vendor/tsugi/lib'$x'"`
    count=$((count+1))
    # if [ ! -f $j ]; then 
      # echo "ERROR: File does not exist" $j
      # exit
    # fi
    echo "cp" $i $j
done

if [[ $count -eq 0 ]]; then
    echo 'No changed files found'
    exit
fi

echo pushd $x
