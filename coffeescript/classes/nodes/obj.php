<?php

namespace CoffeeScript;

class yyObj extends yyBase
{
  public $children = array('properties');

  function __construct($props, $generated = FALSE)
  {
    $this->generated = $generated;
    $this->objects = $this->properties = $props ? $props : array();
  }

  function assigns($name)
  {
    foreach ($this->properties as $prop)
    {
      if ($prop->assigns($name))
      {
        return TRUE;
      }
    }

    return FALSE;
  }

  function compile_node($options)
  {
    $props = $this->properties;

    if ( ! count($props))
    {
      return ($this->front ? '({})' : '{}');
    }

    if ($this->generated)
    {
      foreach ($props as $node)
      {
        if ($node instanceof yyValue)
        {
          throw new Error('cannot have an implicit value in an implicit object');
        }
      }
    }

    $idt = $options['indent'] += TAB;
    $last_non_com = $this->last_non_comment($this->properties);

    foreach ($props as $i => $prop)
    {
      if ($i === count($props) - 1)
      {
        $join = '';
      }
      else if ($prop === $last_non_com || $prop instanceof yyComment)
      {
        $join = "\n";
      }
      else
      {
        $join = ",\n";
      }

      $indent = $prop instanceof yyComment ? '' : $idt;

      if ($prop instanceof yyValue && $prop->this)
      {
        $prop = new yyAssign($prop->properties[0]->name, $prop, 'object');
      }

      if ( ! ($prop instanceof yyComment))
      {
        if ( ! ($prop instanceof yyAssign))
        {
          $prop = new yyAssign($prop, $prop, 'object');
        }

        if (isset($prop->variable->base))
        {
          $prop->variable->base->as_key = TRUE;
        }
        else
        {
          $prop->variable->as_key = TRUE;
        }
      }

      $props[$i] = $indent.$prop->compile($options, LEVEL_TOP).$join;
    }

    $props = implode('', $props);
    $obj = '{'.($props ? "\n{$props}\n{$this->tab}" : '').'}';

    return ($this->front ? "({$obj})" : $obj);
  }
}

?>