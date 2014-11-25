<?php namespace Scale\Kernel\Core;

/**
 * Application Documenter
 *
 * @package    Kernel
 * @category   Base
 * @author     Scale Team
 * @author     Kohana Team
 */

trait Documenter
{

    /**
     *
     * @param string $view
     * @param array  $options
     * @param string $class
     * @return string
     */
    public function document($ns, $view, $options, $class)
    {
        $inspector = new \ReflectionClass($this);

        list($description, $tags) = $this->parseDoccomment($inspector->getDocComment());

        $doc = $this->view(
            $view,
            [
                'description' => $description,
                'tags' => (array) $tags,
                'options' => $options,
                'class' => $class
            ],
            $ns
        );

        return $doc;
    }

    /**
     * Parses a doccomment, extracting both the comment and any tags associated.
     *
     * @param  string $comment The comment to parse
     * @return array contained comment and tags
     */
    protected function parseDoccomment($comment)
    {
        // Normalize all new lines to '\n'
        $comment = str_replace(array("\r\n", "\n"), "\n", $comment);
        // Remove the phpdoc open\close tags and split
        $comment = array_slice(explode("\n", $comment), 1, -1);

        // Tag content
        $tags = array();

        foreach ($comment as $i => $line) {
            // Remove all leading whitespace
            $line = preg_replace('/^\s*\* ?/m', '', $line);

            // Search this line for a tag
            if (preg_match('/^@(\S+)(?:\s*(.+))?$/', $line, $matches)) {
                $tags[$matches[1]] = isset($matches[2]) ? $matches[2] : '';
                unset($comment[$i]);
            } else {
                $comment[$i] = $line;
            }
        }

        $comment = trim(implode(PHP_EOL, $comment));

        return array($comment, $tags);
    }
}
