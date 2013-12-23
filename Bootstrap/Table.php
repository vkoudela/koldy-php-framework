<?php namespace Bootstrap;

class Table extends HtmlElement {

	protected $columns = array();

	protected $data = array();

	protected $primaryKey = 'id';

	protected $renderers = array();

	protected $renderersTd = array();

	protected $renderersTh = array();

	private $dataTable = array();

	public function column($index, $label = null, $width = null, $css = null) {
		$this->columns[$index] = array(
			'label' => ($label === null ? $index : $label),
			'width' => $width,
			'css' => $css
		);
		return $this;
	}

	public function getColumns() {
		return $this->columns;
	}

	public function row($row) {
		$this->data[] = $row;
		return $this;
	}

	public function rows(array $rows) {
		foreach ($rows as $row) {
			$this->row($row);
		}
		return $this;
	}

	public function deleteAll() {
		$this->data = array();
		return $this;
	}

	public function primaryKey($pk) {
		$this->primaryKey = $pk;
		return $this;
	}

	public function getPrimaryKey() {
		return $this->primaryKey;
	}

	public function dataTable(array $dataTableParams = null) {
		$this->addClass('dataTable');
		$this->dataTable = $dataTableParams;
		return $this;
	}

	/**
	 * Add the value renderer
	 * @param string $index the column key
	 * @param function(mixed $value, array $row) $function
	 * @return \Bootstrap\Table
	 */
	public function render($index, $function) {
		$this->renderers[$index] = $function;
		return $this;
	}

	public function getRenderers() {
		return $this->renderers;
	}

	public function renderTd($index, $function) {
		$this->renderersTd[$index] = $function;
		return $this;
	}

	/**
	 * Add the TH renderer. You must return the complete part: <th>something</th>
	 * @param string $index the column key
	 * @param function($index, array $column, array $otherColumns) $function - the $column contains ['label', 'width']
	 * @return Table
	 */
	public function renderTh($index, $function) {
		$this->renderersTh[$index] = $function;
		return $this;
	}

	public function getHtml() {
		if ($this->dataTable !== null) {
			$this->data('table', base64_encode(\Json::encode($this->dataTable)));
		}
		$html = "<table class=\"{$this->getClasses('table table-striped table-hover')}\" id=\"{$this->getId()}\"{$this->getAttributes()}>\n";

		$html .= "<thead>\n\t<tr>\n";
		foreach ($this->columns as $index => $column) {
			if (isset($this->renderersTh[$index])) {
				$fn = $this->renderersTh[$index];
				$html .= $fn($index, $column, $this->columns);
			} else {
				$width = ($column['width'] !== null ? " style=\"width:{$column['width']}px\"" : '');
				$html .= "\t\t<th{$width}>{$column['label']}</th>\n";
			}
		}
		$html .= "\t</tr>\n</thead>\n<tbody>\n";

		foreach ($this->data as $row) {
			if ((is_array($row) && !isset($row[$this->primaryKey]))
				|| (is_object($row) && !($row instanceof \Koldy\Db\Model) && !property_exists($row, $this->primaryKey))
				|| ($row instanceof \Koldy\Db\Model && !$row->has($this->primaryKey)))
			{
				\Log::error("Can not render " . get_class($this) . " because primary field={$this->primaryKey} value doesn't exists: " . print_r($row, true));
			} else {
				if (is_array($row)) {
					$idValue = $row[$this->primaryKey];
				} else {
					$pk = $this->primaryKey;
					$idValue = $row->$pk;
				}
				
				$html .= "\t<tr id=\"{$this->getId()}_{$idValue}\" data-id=\"{$idValue}\">\n";
				foreach ($this->columns as $index => $column) {
					if (isset($this->renderersTd[$index])) {
						$fn = $this->renderersTd[$index];
						$html .= $fn($row);
					} else {
						$add = ($column['css'] !== null) ? " class=\"{$column['css']}\"" : '';
						$html .= "\t\t<td{$add}>";
				
						if (isset($this->renderers[$index])) {
							$fn = $this->renderers[$index];
							$html .= $fn(is_object($row) ? $row->$index : $row[$index], $row);
						} else if (is_array($row) && isset($row[$index])) {
							$html .= $row[$index];
						} else if ($row instanceof \Koldy\Db\Model && $row->has($index)) {
							$html .= $row->$index;
						} else if (is_object($row) && property_exists($row, $index)) {
							$html .= $row->$index;
						}
						$html .= "</td>\n";
					}
				}
				$html .= "\t</tr>\n";
			}
		}

		$html .= "</tbody>\n";

		$html .= "</table>\n";

		return $html;
	}

}