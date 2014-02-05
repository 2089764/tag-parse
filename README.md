## 介绍 Introduction
- - -
This is a php class that used to parse html code and search node.It's efficient.

这是一个用于解析html代码和搜索节点的php类。它是高效的。

## 使用 Usage
- - -
According to different searching condition, there are provide three searching methods:

根据不同的搜索情况，这里提供了3中搜索方法：

1. **Search for a specifical node 查找某个特定节点:**

It's very fast to search a specifical node, because the class store all tag in a static property(name:$TagSet array type) by reference, and the tag name was used as index.

查找一个特定节点是非常快的，因为该类将所有标签都存储在一个静态属性（名为：$TagSet array类型）中，并且以标签的名字作为索引。

eg:
{% highlight php linenos %}
<?php
require 'TagParse/TagDomRoot.php';
$html = '<div><p id="test"> some text </p><p>other text</p></div>';
$TagDomRoot = new TagParse\TagDomRoot($html);
$p = $TagDomRoot->findOneGlobal('p[id="test"]');
?>
{% endhighlight %}

`findOneGlobal()` function will search in `$TagSet` directly, not in dom tree.

`findOneGlobal()`函数会直接在`$TagSet`中查找，而不是在整颗DOM树。

2. **use path for searching 使用路径进行查找:**

There are two different functions: `find()` and `findGlobal()`.

`find()` is used to search in current node's childs. It use Breadth-first search algorithm and will traverse all child nodes. So, just use it when the current node is close to the leaf nodes.

`find()`函数用于在当前节点的孩子节点中进行查找。它使用广度优先搜索算法，会遍历所有的孩子节点。所以，请在靠近叶节点的节点上才使用该函数。

`findGlobal()` is used to search in dom tree, but it doesn't traverse all nodes. Because it will use `findOneGlobal()` to find the first matching nodes, and then use `_seek()` to find out weather the node and it's child match the path. It's doesn't traverse all node's child.

`findGlobal()`函数用于在DOM树中查找，但它不会遍历所有节点。因为它会先使用`findOneGlobal()`函数查找第一个匹配的节点，然后调用`_seek()`函数查找该节点的孩子是否符合path。这也不会对节点的所有孩子进行遍历。

## Node Structure 节点结构
- - -
`public $tag`:                  string tag name
`public $plaintext`:            string the text that node contain
`public $attr`:                 array  node's attributes
`public $parent`:               object node's parent node
`public $child`:                array  the node's child
`public $level`:                int    the node's depth level(root node's level is 0)
`public static $TagParseError`: bool   weather the parsing string contain surplus opening tags or closing tags 
`protected static $TagSet`:     array  store all nodes
`protected static $FoundNode`:  array  store found nodes, it will be array() when find\*() return
`public static $ErrorTag`:      array  store surplus opening tags or closing tags

## Notice 注意
- - -
If you find `$TagParseError` is true after parsing(when you new TagDomRoot), is means that the parsing string contain surplus opening tags or closing tags. This may confuse you when you use the "right" path to find node, but the result is wrong. You may need to change your query condition.

如果你发现`$TagParseError`的值为true时（在实例化类TagDomRoot之后），这意味着被解析的字符串含有多余的开标签或者闭标签。这可能会使你感到疑惑，当你使用“正确”的path进行查找而未能找到正确的节点时。你可能需要修改你的查询条件。

As you know, the TagDOmRoot class contain static property. So, when you want to new another TagDomRoot object in the same script code, the static propertys will not be init, you can use `initProperty()` method to init them.

正如你所知，TagDomRoot类包含了静态的属性。所以，当你在同一个脚本代码中实例化另外一个TagDOmRoot对象时，静态属性仍然保留原来的值，你可以通过`initProperty()`方法初始化它们。

## Conclusions 总结
- - -
You can change code to meet your requires.  If you find any bug about this project, please let me know, thanks!

你可以通过修改代码来满足你的需要。如果你发现本项目存在Bug，请告诉我，谢谢！
