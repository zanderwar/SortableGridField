<?php
/**
 * @package forms
 */
class GridFieldSortableRows implements GridField_HTMLProvider, GridField_ActionProvider, GridField_DataManipulator {
    protected $sortColumn;
    
	/**
	 * @param {string} $sortColumn Column that should be used to update the sort information
	 */
	public function __construct($sortColumn) {
		$this->sortColumn=$sortColumn;
	}
    
    /**
     * Returns a map where the keys are fragment names and the values are pieces of HTML to add to these fragments.
     * @param GridField $gridField Grid Field Reference
     * @return {array} Map where the keys are fragment names and the values are pieces of HTML to add to these fragments.
     */
    public function getHTMLFragments($gridField) {
        $state=$gridField->State->GridFieldSortableRows;
        if(!is_bool($state->sortableToggle)) {
            $state->sortableToggle=false;
        }
        
        
        
        //Sort order toggle
        $sortOrderToggle=new GridField_FormAction($gridField, 'sortablerows_toggle', 'Allow drag and drop re-ordering', 'saveGridRowSort', null);
        $sortOrderToggle->addExtraClass('sortablerows_toggle');
        
        
        //Disable Pagenator
        $disablePagenator=new GridField_FormAction($gridField, 'sortablerows_disablepagenator', 'Disable Pagenator', 'sortableRowsDisablePaginator', null);
        $disablePagenator->addExtraClass('sortablerows_disablepagenator');
        
        
        $forTemplate=new ArrayData(array(
                                        'SortableToggle'=>$sortOrderToggle,
                                        'PagenatorToggle'=>$disablePagenator,
                                        'Checked'=>($state->sortableToggle==true ? ' checked="checked"':'')
                                    ));
        
        
        //Inject Requirements
        Requirements::css('SortableGridField/css/GridFieldSortableRows.css');
        Requirements::javascript('SortableGridField/javascript/GridFieldSortableRows.js');
        
        
        return array(
                    'header'=>$forTemplate->renderWith('GridFieldSortableRows', array('Colspan'=>count($gridField->getColumns())))
                );
        
        return array();
    }
    
    /**
	 * Manipulate the datalist as needed by this grid modifier.
	 * @param {GridField} $gridField Grid Field Reference
	 * @param {SS_List} $dataList Data List to adjust
	 * @return {DataList} Modified Data List
	 */
    public function getManipulatedData(GridField $gridField, SS_List $dataList) {
        return $dataList->sort($this->sortColumn);
    }
    
    /**
     * Return a list of the actions handled by this action provider.
     * @param GridField $gridField Grid Field Reference
     * @return {array} Array with action identifier strings.
     */
    public function getActions($gridField) {
        return array('saveGridRowSort', 'sortableRowsDisablePaginator');
    }
    
    /**
     * Handle an action on the given grid field.
     * @param {GridField} $gridField Grid Field Reference
     * @param {string} $actionName Action identifier, see {@link getActions()}.
     * @param {array} $arguments Arguments relevant for this
     * @param {array} $data All form data
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
        $state=$gridField->State->GridFieldSortableRows;
        if(!is_bool($state->sortableToggle)) {
            $state->sortableToggle=false;
        }else if($state->sortableToggle==true) {
            if($gridField->getConfig()->getComponentsByType('GridFieldPaginator')) {
                $gridField->getConfig()->removeComponentsByType('GridFieldPaginator');
                $gridField->getConfig()->addComponent(new GridFieldFooter());
            }
            
            $gridField->getConfig()->removeComponentsByType('GridFieldFilterHeader');
            $gridField->getConfig()->removeComponentsByType('GridFieldSortableHeader');
        }
        
        
        if($actionName=='savegridrowsort') {
            return $this->saveGridRowSort($gridField, $data);
        }
    }
    
    /**
     * Handles saving of the row sort order
     * @param {GridField} $gridField Grid Field Reference
     * @param {array} $data Data submitted in the request
     */
    private function saveGridRowSort(GridField $gridField, $data) {
        if(empty($data['Items'])) {
            user_error('No items to sort', E_USER_ERROR);
        }
        
        $className=$gridField->getModelClass();
        $owner=$gridField->Form->getRecord();
        $items=$gridField->getList();
        $many_many=($items instanceof ManyManyList);
        $sortColumn=$this->sortColumn;
        
        
        if($many_many) {
            list($parentClass, $componentClass, $parentField, $componentField, $table)=$owner->many_many($gridField->getName());
        }
        
        
        $data['Items']=explode(',', $data['Items']);
        for($sort=0;$sort<count($data['Items']);$sort++) {
            $id=intval($data['Items'][$sort]);
            if($many_many) {
                DB::query('UPDATE "'.$table.'" SET "'.$sortColumn.'"='.($sort+1).' WHERE "'.$componentField.'"='.$id.' AND "'.$parentField.'"='.$owner->ID);
            }else {
                $obj=$items->byID($data['Items'][$sort]);
                $obj->$sortColumn=$sort+1;
                $obj->write();
            }
        }
    }
}
?>