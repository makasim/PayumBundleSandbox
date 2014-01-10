<?php

namespace Acme\DemoBundle\Twig\Extension;

use CG\Core\ClassUtils;
use Symfony\Component\Yaml\Yaml;

class DemoExtension extends \Twig_Extension
{
    protected $loader;
    protected $controller;

    public function __construct(\Twig_LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'code' => new \Twig_Function_Method($this, 'getCode', array('is_safe' => array('html'))),
            'payum_context' => new \Twig_Function_Method($this, 'getPayumContext'),
        );
    }

    public function getCode($template, $paymentContext = null)
    {
        $payumConfigHtml = '';
        if ($paymentContext) {
            ob_start();
            include __DIR__.'/../../../../../app/config/payum.yml';
            $config = Yaml::parse(ob_get_clean());
            $payumConfig = Yaml::dump(
                array(
                    'payum' => array(
                        'security' => $config['payum']['security'],
                        'contexts' => array(
                            $paymentContext => $config['payum']['contexts'][$paymentContext]
                        )
                    )
                ),
                $inline = 10
            );

            $payumConfigHtml = "<p><strong>Payum config:</strong></p><pre># app/config/payum.yml\n\n$payumConfig</pre>";
        }

        // highlight_string highlights php code only if '<?php' tag is present.
        $controller = highlight_string("<?php" . $this->getControllerCode(), true);
        $controller = str_replace('<span style="color: #0000BB">&lt;?php&nbsp;&nbsp;&nbsp;&nbsp;</span>', '&nbsp;&nbsp;&nbsp;&nbsp;', $controller);

        $template = htmlspecialchars($this->getTemplateCode($template), ENT_QUOTES, 'UTF-8');

        // remove the code block
        $template = str_replace('{% set code = code(_self) %}', '', $template);

        return <<<EOF
<p><strong><a name="whats-inside?">What's inside?</a></strong></p>

$payumConfigHtml

<p><strong>Controller Code</strong></p>
<pre>$controller</pre>

<p><strong>Template Code</strong></p>
<pre>$template</pre>
EOF;
    }

    protected function getControllerCode()
    {
        $class = get_class($this->controller[0]);
        if (class_exists('CG\Core\ClassUtils')) {
            $class = ClassUtils::getUserClass($class);
        }

        $r = new \ReflectionClass($class);
        $m = $r->getMethod($this->controller[1]);

        $code = file($r->getFilename());

        return '    '.$m->getDocComment()."\n".implode('', array_slice($code, $m->getStartline() - 1, $m->getEndLine() - $m->getStartline() + 1));
    }

    protected function getTemplateCode($template)
    {
        return $this->loader->getSource($template->getTemplateName());
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'demo';
    }
}
