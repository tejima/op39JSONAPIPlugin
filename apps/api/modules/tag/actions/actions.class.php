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
  const API_LIMIT = 300;//FIXME
  
  public function executeByEntity(sfWebRequest $req){
    $this->forward400If('' === (string)$req['entity'], 'entity not specified.');

    $tag_list = Doctrine_Query::create()->select('t.tag')->from("Tag t")
      ->where("entity = ?",$req['entity'])->limit(API_LIMIT)->execute(array(), Doctrine_Core::HYDRATE_NONE);
    $result = array();
    foreach($tag_list as $tag){
      $result[] = $tag[0];
    }
    return $this->renderText(json_encode(array("status"=>"success" , "data" => $result)));
    //$tag_tarms = json_decode(Doctrine::getTable('SnsConfig')->get("tag_terms"),true);
    //return $this->renderText(json_encode($tag_terms));
  }
  public function executeTopic(sfWebRequest $req)
  {
    $this->forward400If('' === (string)$req['tag'], 'tag not specified.');
    $query = Doctrine_Query::create()->from("Tag t")->where("t.tag = ?",$req['tag'])->andWhere("entity like 'T%'")->orderBy("t.id desc")->limit(API_LIMIT);
    $tag_list = $query->execute()->toArray();
    $tag_list_array = array();
    foreach($tag_list as $tag){
      $tag_list_array[] = substr($tag['entity'],1);
    }

    if($req['tag_minus']){
      $query = Doctrine_Query::create()->from("Tag t")->where("t.tag = ?",$req['tag_minus'])->andWhere("entity like 'T%'")->orderBy("t.id desc")->limit(API_LIMIT);
      $minus_tag_list = $query->execute()->toArray();
      $minus_tag_list_array = array();
      foreach($minus_tag_list as $minus_tag){
        $minus_tag_list_array[] = substr($minus_tag['entity'],1);
      }
      $tag_list_array = array_diff($tag_list_array,$minus_tag_list_array);
    }

    $result = array_values($tag_list_array);
    if((int)$req['count'] > 0){
      $result = array_slice($result, 0, (int)$req['count']);
    }
    
    $this->getRequest()->setParameter('target_id', $result);
    $this->getRequest()->setParameter('target', 'topic');
    $this->forward("communityTopic","search");
  }
  public function executeAssign(sfWebRequest $req)
  {
    $this->forward400If('' === (string)$req['tag'], 'tag not specified.');
    $this->forward400If('' === (string)$req['entity'], 'entity not specified.');

    if($req['remove']){
      Doctrine_Query::create()->delete()->from("Tag t")->where("tag = ?",$req['tag'])->andWhere('entity = ?',$req['entity'])->execute();
        return $this->renderText(json_encode(array("status"=>"success","message"=>"TAG REMOVED")));
    }else{
      $result = Doctrine_Query::create()->from("Tag t")->where("tag = ?",$req['tag'])->andWhere('entity = ?',$req['entity'])->limit(1)->fetchArray();
      if($result[0]){
        return $this->renderText(json_encode(array("status"=>"success","message"=>"SAME KEY no action.")));
      }else{
        //FIXME entity exsiting check.
        $tag = new Tag();
        $tag->entity = $req['entity'];
        $tag->tag = $req['tag'];
        $tag->save();
        return $this->renderText(json_encode(array("status"=>"success","message"=>"NEW KEY save tag.")));
      }
    }
  }
  public function executeList(sfWebRequest $req)
  {
    $con = Doctrine_Core::getTable('Tag')->getConnection();
    $tag_list = $con->fetchAll('select distinct tag from tag');
    //$ar = array("status"=>"success","message"=>"NEW KEY save tag.");
    $result = array();
    foreach($tag_list as $key => $value){
      $result[] = $value['tag'];
    }
    return $this->renderText(json_encode(array("status"=>"success" ,"message"=>"", "data" => $result)));
  }
}