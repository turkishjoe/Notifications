<?php

/**
 * This file is a part of [Telegram] Notifications.
 * All rights reserved.
 *
 * Developed by SourceModders.
 */

namespace SModders\TelegramNotifications\Service;


use XF\Service\AbstractService;

class HtmlPurifier extends AbstractService
{
    /**
     * @var array
     */
    protected $rules;
    
    /**
     * @var string
     */
    protected $text = '';

    public function __construct(\XF\App $app, array $rules)
    {
        parent::__construct($app);
        $this->rules = $rules;
    }
    
    /**
     * @param string $text
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }
    
    /**
     * @param array $rules
     * @return $this
     */
    public function setRules($rules)
    {
        $this->rules = $rules;
        return $this;
    }
    
    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }
    
    /**
     * @param $text
     * @return string
     */
    public function purify($text)
    {
        return $this->setText($text)
            ->stripTags()
            ->stripAttributes()
            ->getText();
    }
    
    /**
     * @return $this
     */
    public function stripTags()
    {
        $tags = '';
        foreach (array_keys($this->rules) as $tag)
        {
            $tags .= "<{$tag}>";
        }

        $this->text = strip_tags($this->text, $tags);
        return $this;
    }
    
    /**
     * @return $this
     */
    public function stripAttributes()
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        if (!$dom->loadHTML('<?xml encoding="UTF-8">' . $this->text))
        {
            return $this;
        }

        foreach ($dom->childNodes as $item)
        {
            if ($item->nodeType == XML_PI_NODE)
            {
                $dom->removeChild($item);
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $this->walkNode($body);

        $newDom = new \DOMDocument();
        $newDom->appendChild($newDom->importNode($body->ownerDocument->documentElement->firstChild, true));

        $this->text = str_replace('&nbsp;', '', $newDom->saveHTML());
        $this->stripTags();
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function render()
    {
        return trim($this->text);
    }
    
    /**
     * @param \DOMNode $node
     */
    protected function walkNode(\DOMNode $node)
    {
        // Clear attributes (if required).
        if (isset($this->rules[$node->nodeName]))
        {
            $this->walkAttributes($node);
        }

        // Clear childrens.
        $this->walkChildrens($node);
    }
    
    /**
     * @param \DOMNode $node
     */
    protected function walkAttributes(\DOMNode $node)
    {
        if (!$node->hasAttributes())
        {
            return;
        }
        
        $allowedAttributes = $this->rules[$node->nodeName];
        $attributesToDelete = [];
        foreach ($node->attributes as $attribute)
        {
            $name = $attribute->name;
            if (!in_array($name, $allowedAttributes))
            {
                $attributesToDelete[] = $attribute;
            }
        }

        foreach ($attributesToDelete as $attribute)
        {
            $node->removeAttribute($attribute->name);
        }
    }
    
    /**
     * @param \DOMNode $node
     */
    protected function walkChildrens(\DOMNode $node)
    {
        if (!$node->hasChildNodes())
        {
            return;
        }

        foreach ($node->childNodes as $childNode)
        {
            $this->walkNode($childNode);
        }
    }
}