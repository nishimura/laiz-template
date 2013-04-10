# laiz/template: Simple Template Engine

laiz/template is simple template engine that is developed with PHP5.3.

## Usage

Add `cache` directory in project directory:

    cd public_html
    mkdir cache
    chmod o+w cache

Include ``Template.php`` or ``Parser.php`` file:

    cat > index.php
    <?php
    require_once 'Laiz/Template/Parser.php';
    $t = new Laiz\\Template\\Parser();
    $vars = new StdClass();
    $vars->foo = 'World!';
    $t->show($vars);

Add template file of top page:

    mkdir template
    echo 'Hello {foo}' > template/index.html
