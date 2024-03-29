<?php

Yii::import('ext.xul.widgets.XulBaseListView');

class XulListView extends XulBaseListView
{
	/**
	 * @var string the view used for rendering each data item.
	 * This property value will be passed as the first parameter to either {@link CController::renderPartial}
	 * or {@link CWidget::render} to render each data item.
	 * In the corresponding view template, the following variables can be used in addition to those declared in {@link viewData}:
	 * <ul>
	 * <li><code>$this</code>: refers to the owner of this list view widget. For example, if the widget is in the view of a controller,
	 * then <code>$this</code> refers to the controller.</li>
	 * <li><code>$data</code>: refers to the data item currently being rendered.</li>
	 * <li><code>$index</code>: refers to the zero-based index of the data item currently being rendered.</li>
	 * <li><code>$widget</code>: refers to this list view widget instance.</li>
	 * </ul>
	 */
	public $itemView;
	/**
	 * @var string the HTML code to be displayed between any two consecutive items.
	 * @since 1.1.7
	 */
	public $separator;
	/**
	 * @var array additional data to be passed to {@link itemView} when rendering each data item.
	 * This array will be extracted into local PHP variables that can be accessed in the {@link itemView}.
	 */
	public $viewData=array();
	/**
	 * @var array list of sortable attribute names. In order for an attribute to be sortable, it must also
	 * appear as a sortable attribute in the {@link IDataProvider::sort} property of {@link dataProvider}.
	 * @see enableSorting
	 */
	public $sortableAttributes;
	/**
	 * @var string the template to be used to control the layout of various components in the list view.
	 * These tokens are recognized: {summary}, {sorter}, {items} and {pager}. They will be replaced with the
	 * summary text, the sort links, the data item list, and the pager.
	 */
	public $template="{summary}\n{sorter}\n{items}\n{pager}";
	/**
	 * @var string the CSS class name that will be assigned to the widget container element
	 * when the widget is updating its content via AJAX. Defaults to 'list-view-loading'.
	 * @since 1.1.1
	 */
	public $loadingCssClass='list-view-loading';
	/**
	 * @var string the CSS class name for the sorter container. Defaults to 'sorter'.
	 */
	public $sorterCssClass='sorter';
	/**
	 * @var string the text shown before sort links. Defaults to 'Sort by: '.
	 */
	public $sorterHeader;
	/**
	 * @var string the text shown after sort links. Defaults to empty.
	 */
	public $sorterFooter='';
	/**
	 * @var mixed the ID of the container whose content may be updated with an AJAX response.
	 * Defaults to null, meaning the container for this list view instance.
	 * If it is set false, it means sorting and pagination will be performed in normal page requests
	 * instead of AJAX requests. If the sorting and pagination should trigger the update of multiple
	 * containers' content in AJAX fashion, these container IDs may be listed here (separated with comma).
	 */
	public $ajaxUpdate;
	/**
	 * @var string the jQuery selector of the HTML elements that may trigger AJAX updates when they are clicked.
	 * If not set, the pagination links and the sorting links will trigger AJAX updates.
	 * @since 1.1.7
	 */
	public $updateSelector;
	/**
	 * @var string the name of the GET variable that indicates the request is an AJAX request triggered
	 * by this widget. Defaults to 'ajax'. This is effective only when {@link ajaxUpdate} is not false.
	 */
	public $ajaxVar='ajax';
	/**
	 * @var string a javascript function that will be invoked before an AJAX update occurs.
	 * The function signature is <code>function(id)</code> where 'id' refers to the ID of the list view.
	 */
	public $beforeAjaxUpdate;
	/**
	 * @var string a javascript function that will be invoked after a successful AJAX response is received.
	 * The function signature is <code>function(id, data)</code> where 'id' refers to the ID of the list view
	 * 'data' the received ajax response data.
	 */
	public $afterAjaxUpdate;
	/**
	 * @var string the base script URL for all list view resources (e.g. javascript, CSS file, images).
	 * Defaults to null, meaning using the integrated list view resources (which are published as assets).
	 */
	public $baseScriptUrl;
	/**
	 * @var string the URL of the CSS file used by this list view. Defaults to null, meaning using the integrated
	 * CSS file. If this is set false, you are responsible to explicitly include the necessary CSS file in your page.
	 */
	public $cssFile;
	/**
	 * @var string the HTML tag name for the container of all data item display. Defaults to 'div'.
	 * @since 1.1.4
	 */
	public $itemsTagName='vbox';

	/**
	 * Initializes the list view.
	 * This method will initialize required property values and instantiate {@link columns} objects.
	 */
	public function init()
	{
		if($this->itemView===null)
			throw new CException(Yii::t('xul','The property "itemView" cannot be empty.'));
		parent::init();

		if(!isset($this->xulOptions['class']))
			$this->xulOptions['class']='list-view';

		if($this->baseScriptUrl===null)
			$this->baseScriptUrl=Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('zii.widgets.assets')).'/listview';

		if($this->cssFile!==false)
		{
			if($this->cssFile===null)
				$this->cssFile=$this->baseScriptUrl.'/styles.css';
			Yii::app()->getClientScript()->registerCssFile($this->cssFile);
		}
	}

	/**
	 * Registers necessary client scripts.
	 */
	public function registerClientScript()
	{
		$id=$this->getId();

		if($this->ajaxUpdate===false)
			$ajaxUpdate=array();
		else
			$ajaxUpdate=array_unique(preg_split('/\s*,\s*/',$this->ajaxUpdate.','.$id,-1,PREG_SPLIT_NO_EMPTY));
		$options=array(
			'ajaxUpdate'=>$ajaxUpdate,
			'ajaxVar'=>$this->ajaxVar,
			'pagerClass'=>$this->pagerCssClass,
			'loadingClass'=>$this->loadingCssClass,
			'sorterClass'=>$this->sorterCssClass,
		);
		if($this->updateSelector!==null)
			$options['updateSelector']=$this->updateSelector;
		if($this->beforeAjaxUpdate!==null)
			$options['beforeAjaxUpdate']=(strpos($this->beforeAjaxUpdate,'js:')!==0 ? 'js:' : '').$this->beforeAjaxUpdate;
		if($this->afterAjaxUpdate!==null)
			$options['afterAjaxUpdate']=(strpos($this->afterAjaxUpdate,'js:')!==0 ? 'js:' : '').$this->afterAjaxUpdate;

		$options=CJavaScript::encode($options);
		$cs=Yii::app()->getClientScript();
		//$cs->registerCoreScript('jquery');
		//$cs->registerCoreScript('bbq');
		//$cs->registerScriptFile($this->baseScriptUrl.'/jquery.yiilistview.js',CClientScript::POS_END);
		//$cs->registerScript(__CLASS__.'#'.$id,"jQuery('#$id').yiiListView($options);");
	}

	/**
	 * Renders the data item list.
	 */
	public function renderItems()
	{
		echo Xul::xopenTag($this->itemsTagName,array('class'=>$this->itemsCssClass))."\n";
		$data=$this->dataProvider->getData();
		if(($n=count($data))>0)
		{
			$owner=$this->getOwner();
			$render=$owner instanceof CController ? 'renderPartial' : 'render';
			$j=0;
			foreach($data as $i=>$item)
			{
				$data=$this->viewData;
				$data['index']=$i;
				$data['data']=$item;
				$data['widget']=$this;
				$owner->$render($this->itemView,$data);
				if($j++ < $n-1)
					echo $this->separator;
			}
		}
		else
			$this->renderEmptyText();
		echo Xul::xcloseTag($this->itemsTagName);
	}

	/**
	 * Renders the sorter.
	 */
	public function renderSorter()
	{
		if($this->dataProvider->getItemCount()<=0 || !$this->enableSorting || empty($this->sortableAttributes))
			return;
		echo Xul::xopenTag('box',array('class'=>$this->sorterCssClass))."\n";
		echo $this->sorterHeader===null ? Yii::t('xul','Sort by: ') : $this->sorterHeader;
		echo "<ul>\n";
		$sort=$this->dataProvider->getSort();
		foreach($this->sortableAttributes as $name=>$label)
		{
			echo "<li>";
			if(is_integer($name))
				echo $sort->link($label);
			else
				echo $sort->link($name,$label);
			echo "</li>\n";
		}
		echo "</ul>";
		echo $this->sorterFooter;
		echo Xul::xcloseTag('box');
	}
}
