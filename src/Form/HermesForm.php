<?php

namespace NanoSoup\Hermes\Form;

use Symfony\Component\Form\Form;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Component\Form\FormRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Forms;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

class HermesForm extends Form
{
    /**
     * HermesForm constructor.
     *
     * @param \Symfony\Component\Form\FormConfigInterface $config
     */
    public function __construct()
    {
        add_filter('twig_apply_filters', function ($twig) {
            $defaultFormTheme = 'form_div_layout.html.twig';

            $appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
            $vendorTwigBridgeDirectory = dirname($appVariableReflection->getFileName());
            // the path to your other templates
            $viewsDirectory = realpath(get_stylesheet_directory());
            $acfViewsDirectory = $viewsDirectory . '/classes/ACF/Blocks/views/';

            $twig = new Environment(new FilesystemLoader([
                $viewsDirectory,
                $acfViewsDirectory,
                $vendorTwigBridgeDirectory . '/Resources/views/Form',
            ]));

            $formEngine = new TwigRendererEngine([$defaultFormTheme], $twig);

            $twig->addRuntimeLoader(new FactoryRuntimeLoader([
                FormRenderer::class => function () use ($formEngine) {
                    return new FormRenderer($formEngine);
                },
            ]));

            $twig->addExtension(new FormExtension());

            // creates the Translator
            $translator = new Translator('en');

            // somehow load some translations into it
            $translator->addLoader('xlf', new XliffFileLoader());

            // adds the TranslationExtension (gives us trans and transChoice filters)
            $twig->addExtension(new TranslationExtension($translator));

            return $twig;
        }, 10, 3);
    }

    /**
     * @param $form
     * @return Form
     */
    public function getForm($form): \Symfony\Component\Form\Form
    {
        $factory = Forms::createFormFactoryBuilder()
            ->getFormFactory();

        $form = $factory->create($form);

        return $form;
    }

    /**
     * Pushes submissions from forms to the data layer
     *
     * @param $form - The name of the form type submitted
     * @return string
     */
    public function push_submission_gtm($form, $event = 'formSubmission'): string
    {
        $confirmation =
            "<script>" .
            "    window.dataLayer = window.dataLayer || [];" .
            "    window.dataLayer.push({" .
            "        'event': '" . $event . "'," .
            "        'formId': '" . $form['id'] . "'" .
            "    });" .
            "</script>";

        return $confirmation;
    }

    /**
     * @param $data
     * @param $subject
     * @param $template
     * @param null $to
     * @param null $from
     * @return bool
     */
    public function send($data, $subject, $template, $to = null, $from = null): bool
    {
        return wp_mail($to, $subject, \Timber::compile($template, $data));
    }
}