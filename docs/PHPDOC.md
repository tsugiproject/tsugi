Writing Module, Class and Function Documentation
================================================

If you dont have phpdoc installed, you can download the
phar version using this or similar.   Put the file right in the top
directory of tsugi.

    curl -O http://www.phpdoc.org/phpDocumentor.phar

On ubuntu you should install:

    apt-get install php5-cli
    apt-get install graphviz

If you dont install these (i.e. linke on a Mac) you will see this error:

Unable to find the `dot` command of the GraphViz package. Is GraphViz correctly installed and present in your path?

Run phpdoc as follows

    rm -r .tmp_phpdoc .out_phpdoc
    php phpDocumentor.phar -c phpdoc.dist.xml
    rm -r .tmp_phpdoc

And your documentation will be in .out_phpdoc

The output and parse data have dots by default sothings like grep
dont find them by mistake.


