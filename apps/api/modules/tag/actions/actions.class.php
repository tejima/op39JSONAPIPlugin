<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * community topic api actions.
 *
 * @package    OpenPNE
 * @subpackage action
 */
class tagActions extends opJsonApiActions
{
  public function executeByEntity(sfWebRequest $req){
    $this->forward400If('' === (string)$req['entity'], 'entity parameter is not specified.');
    //FIXME
    $foreign_table = substr($req['entity'],0,1);
    $foreign_id = substr($req['entity'],1);

    $id2term_list = $this->id2term_list();

    $entity_tag_list = Doctrine_Query::create()->select('t.tag_id')->from("Tag t")
      ->where("foreign_id = ?",$foreign_id)->andWhere("foreign_table = ?" ,$foreign_table)->execute(array(), Doctrine_Core::HYDRATE_NONE);

    $result = array();
    foreach($id2term_list as $key => $value){
      $result[$key]['tag'] = $value;
      if(in_array(array($key),$entity_tag_list)){
        $result[$key]['assign'] = "1";
      }else{
        $result[$key]['assign'] = "0";
      }
    }

    $ar = array("status"=>"success" , "data" => $result);
    return $this->renderText(json_encode($ar));
    //$tag_tarms = json_decode(Doctrine::getTable('SnsConfig')->get("tag_terms"),true);
    //return $this->renderText(json_encode($tag_terms));
  }
  public function executeTopic(sfWebRequest $req)
  {
    $this->forward400If('' === (string)$req['tag_id'], 'tag parameter is not specified.');
    $tag_id = $req['tag_id'];

    $this->forward400If(null == $tag_id , 'tag parameter not match.');

    $list = Doctrine_Query::create()->from("Tag t")->where("tag_id = ?",$tag_id)->andWhere("foreign_table = 'T'")->execute();
    $result = array();
    foreach($list as $line){
      $result[] = $line['foreign_id'];
    }
    //FIXME clear preset parameters
    
    $this->getRequest()->setParameter('target_id', $result);
    $this->getRequest()->setParameter('target', 'topic');
    $this->forward("communityTopic","search");
  }
  public function executeAssign(sfWebRequest $req)
  {
    $this->forward400If('' === (string)$req['tag_id'], 'tag_id parameter is not specified.');
    $this->forward400If('' === (string)$req['entity'], 'entity parameter is not specified.');

    $foreign_table = substr($req['entity'],0,1);
    $foreign_id = substr($req['entity'],1);
    if($req['remove']){
      Doctrine_Query::create()->delete()->from("Tag t")->where("tag_id = ?",$req['tag_id'])->andWhere('foreign_id = ?',$foreign_id)->andWhere('foreign_table = ?',$foreign_table)->execute();
        $ar = array("status"=>"success","message"=>"TAG REMOVED");
        return $this->renderText(json_encode($ar));
    }else{
      $result = Doctrine_Query::create()->from("Tag t")->where("tag_id = ?",$req['tag_id'])->andWhere('foreign_id = ?',$foreign_id)->andWhere('foreign_table = ?',$foreign_table)->limit(1)->fetchArray();
      if($result[0]){
        $ar = array("status"=>"success","message"=>"SAME KEY no action.");
        return $this->renderText(json_encode($ar));
      }else{
        //FIXME entity exsiting check.
        $tag = new Tag();
        $tag->foreign_id = $foreign_id;
        $tag->foreign_table = $foreign_table;
        $tag->tag_id = $req['tag_id'];
        $tag->save();
        $ar = array("status"=>"success","message"=>"NEW KEY save tag.");
        return $this->renderText(json_encode($ar));
      }
    }
  }
  public function executeList(sfWebRequest $req)
  {
    return $this->renderText(json_encode($this->term2id_list()));  
  }
  private function term2id_list(){
    return json_decode(Doctrine::getTable('SnsConfig')->get("tag_terms"),true);
  }
  private function id2term_list(){
    return array_flip($this->term2id_list());
  }
}
