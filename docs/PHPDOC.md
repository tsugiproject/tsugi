Writing Module, Class and Function Documentation
================================================

If you dont have phpdoc installed, you can download the 
phar version using this or similar.   Put the file right in the top 
directory of tsugi.

    curl -O http://www.phpdoc.org/phpDocumentor.phar 

Run phpdoc as follows

    rm -r phpdoc phpdoc_tmp
    phpdoc -c phpdoc.dist.xml
    rm -r phpdoc_tmp

or

    rm -r phpdoc phpdoc_tmp
    php phpDocumentor.phar -c phpdoc.dist.xml
    rm -r phpdoc_tmp

And your documentation will be in *phpdoc*

