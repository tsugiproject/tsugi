Writing Module, Class and Function Documentation
================================================

If you dont have phpdoc installed, you can download the 
phar version using this or similar.   Put the file right in the top 
directory of tsugi.

    curl -O http://www.phpdoc.org/phpDocumentor.phar 

Run phpdoc as follows

    rm -r phpdoc tmp_phpdoc
    phpdoc -c phpdoc.dist.xml
    rm -r tmp_phpdoc

or

    rm -r phpdoc tmp_phpdoc
    php phpDocumentor.phar -c phpdoc.dist.xml
    rm -r tmp_phpdoc

And your documentation will be in *phpdoc*

