<?php
if( !defined('PATH_ROOT') ) exit('Request Error!');
/**
 *
 * 简易文本数据库类 (NoSQL数据存储)
 * 传入的字段数据需要addslashes转义
 * 本程序使用 serialize 和 unserialize 方式保存和载入数据，只支持php5
 *
 * 使用说明： 设定好 CACHE_DB 数据库保存目录，并在这个目录手动设置一份tables.config文件，即可进行各项操作
 * tables.config 文件示例：
 * <?xml version="1.0" encoding="UTF-8" ?>
 * <!-- 配置中的属性必须带 " 或 ' ，否则可能解析错误 -->
 * <root>
 *    <table name="catalog">
 *       <field name="cid" type="int" length="4" default="1" add="primarykey" />
 *       <field name="pid" type="int" length="4" default="0" />
 *       <field name="sortid" type="int" length="4" default="" />
 *       <field name="typename" type="varchar" length="30" default="" />
 *    </table>
 * </root>
 * 在指定了表配置的情况下，向系统插入第一条数据后会生成表的具体数据文件，不需要单独处理。
 *
 * 本类仅适用于数据量不大的情况下，代替sql存储的方法
 *
 */
define('CACHE_DB', CACHE_DB_DIR);
class cls_cache_db
{
    //字段类型
    private $field_types = array('int', 'float', 'varchar', 'text', 'set');
    //表信息
    public $tables = array();
    
    //开启此信息发生错误时，会输出错误并中断，否则不处理错误
    public static $show_error = true;
    
    public static $cdb = '';
    
    //记录集游标(保存结果总数等信息)
    public static $select_cursor = 0;
    public static $select_cursors = array();
    
    //上次insert的id
    public static $insertid = 0;
    
   /**
    * 初始化类读取表的基本信息
    */
    public function __construct()
	  {
	     $this->analyse_config();
	  }
	  
	 /**
	  * 用静态方法初始化类
	  */
	  public static function factory($tablename='')
	  {
	     if( self::$cdb == '' ) {
	        self::$cdb =& new cls_cache_db();
	     }
	     if($tablename != '') {
	        self::$cdb->get_table_datas($tablename);
	     }
	     return self::$cdb;
	  }
	  
	 /**
	  * 向指定表插入一条或多条记录
	  * @parem $tablename 表名
	  * @parem $keyvalues 字段键值对
	  * @parem $more  是否插入多条记录（如果此值为true，那么 $keyvalues 应该为二维hash数组）
	  */
	  public static function insert($tablename, $keyvalues, $more=false)
	  {
	      $cdb = self::factory($tablename);
	      if( $cdb->tables[$tablename]['datas'] === false )
	      {
	         return false;
	      }
	      if( $more===true ) {
	         $keyvaluess = $keyvalues;
	      } else {
	         $keyvaluess[] = $keyvalues;
	      }
	      $prikey = $cdb->tables[$tablename]['key'];
	      foreach($keyvaluess as $keyvalues)
	      {
	         if( isset($keyvalues[$prikey]) && isset( $cdb->tables[$tablename]['datas'][0][$keyvalues[$prikey]] ) )
	         {
	            return self::dispose_error("primarykey `$prikey` value has exist!");
	         }
	         if( $cdb->tables[$tablename]['datas']=='' )
	         {
	             $cdb->tables[$tablename]['datas'] = array();
	             $cdb->tables[$tablename]['datas'][1] = 0;
	         }
	         $cdb->tables[$tablename]['datas'][1]++;
	         $keyvalues[$prikey] = isset($keyvalues[$prikey]) ? $keyvalues[$prikey] : $cdb->tables[$tablename]['datas'][1];
	         $cdb->tables[$tablename]['datas'][1] = self::$insertid = $keyvalues[$prikey];
	         $nrow = array();
	         foreach( $cdb->tables[$tablename]['fields'] as $field_name => $field_config )
	         {
	            $fvalue = isset($keyvalues[$field_name]) ? $keyvalues[$field_name] : $field_config['default'];
	            $cdb->modify_field_value($field_config, $fvalue);
	            if($field_config['type']=='text')
	            {
	                $cdb->save_text_data($tablename, $field_name, $cdb->tables[$tablename]['datas'][1], $fvalue);
	                $fvalue = strlen($fvalue);
	            }
	            $nrow[$field_name] = $fvalue;
	         }
	         $cdb->tables[$tablename]['datas'][0][$keyvalues[$prikey]] = $nrow;
	         $cdb->tables[$tablename]['count']++;
	      }
	      $cdb->tables[$tablename]['datas'][2] = $cdb->tables[$tablename]['count'];
	      $rs = $cdb->save_table_datas($tablename, $cdb->tables[$tablename]['datas']);
	      return $rs;
	  }
	  
	 /**
	  * 在指定表更新一条或多条记录
	  * @parem $tablename 表名
	  * @parem $keyvalues 字段键值对
	  * @parem $conditions  array 查询条件
	  * @parem $matchmode 查询条件模式 1|0|2（在 1 模式下，当其中一个条件为假，即认为是假， 在 0 模式下，有一个模式为真，即认为是真）
	  *                  指定为 2 模式的情况下 $conditions 是主键值的数组(单个值时会被转为只有一个元素的数组)
	  * @parem $issave 是否马上保存数据
	  *
	  *  具体用法说明：
	  *  1、按指定条件更新 如: cls_cache_db::update('catalog', array('typename'=>'new name'), " @cid = 50 ", 1);
	  *  2、更新时使用原来行的某字段：cls_cache_db::update('catalog', array('typename'=>'new name @cid@'), " @cid < 50 ", 1);
	  *  3、按主键更新：cls_cache_db::update('catalog', array('typename'=>'new name'), array(49, 50), 2);
	  *  4、@:字段运算符@（只支持++/--/+/-，并且只适用于int、float类型） 如：cls_cache_db::update('catalog', array('pid'=>'@:+@cid'), array(49, 50), 2);
	  */
	  public static function update($tablename, $keyvalues, $conditions, $matchmode=1, $issave=true)
	  {
	      $affect_record = 0;
	      $cdb = self::factory($tablename);
	      if( $cdb->tables[$tablename]['datas'] === false )
	      {
	         return false;
	      }
	      $prikey = $cdb->tables[$tablename]['key'];
	      
	      //主键匹配模式
	      $makev = array();
	      if( $matchmode==2 )
	      {
	         if( !is_array($conditions) ) {
	            $conditionss[] = $conditions;
	         } else {
	            $conditionss = $conditions;
	         }
	         foreach($conditionss as $privalue)
	         {
	            if( isset($cdb->tables[$tablename]['datas'][0][$privalue]) )
	            {
	                $row = $cdb->tables[$tablename]['datas'][0][$privalue];
	                foreach($keyvalues as $k => $v)
                  {
                      if( !isset( $cdb->tables[$tablename]['fields'][$k] ) )
                      {
                        continue;
                      }
                      if( is_array($v) || empty($v) )
                      {
                          $makev[$k][0] = $makev[$k][1] = '';
                      }
                      else if( !isset($makev[$k][0]) )
                      {
                            $arr = '';
                            preg_match_all("/@([a-z0-9_-]*)@/iU", $v, $arr);
                            $makev[$k][0] = !empty($arr[1]) ? $arr[1] : '';
                            $arr = '';
                            preg_match_all("/@:([\+\-]*)@/iU", $v, $arr);
                            if( isset($arr[1][0]) )
                            {
                               $makev[$k][1] = $arr[1][0];
                               $makev[$k][2] = trim( str_replace($arr[0][0], '', $v) );
                            }
                            else
                            {
                               $makev[$k][1] = $makev[$k][2] = '';
                            }
                      }
                      if( $makev[$k][0] != '' )
                      {
                            foreach($makev[$k][0] as $e)
                            {
                                if(isset($row[$k])) {
                                    $v = str_replace("@{$e}@", $row[$k], $v);
                                }
                            }
                      }
                      else if( $makev[$k][1] != '' )
                      {
                         if( $makev[$k][1]=='--' ) {
                            $row[$k]--;
                         }
                         else if( $makev[$k][1]=='++' ) {
                            $row[$k]++;
                         }
                         else if( $makev[$k][1]=='-' && $makev[$k][2] != '' ) {
                            $row[$k] = $row[$k] - (isset($row[$makev[$k][2]]) ? $row[$makev[$k][2]] : $makev[$k][2]);
                         }
                         else if( $makev[$k][1]=='+' && $makev[$k][2] != '' ) {
                            $row[$k] = $row[$k] + (isset($row[$makev[$k][2]]) ? $row[$makev[$k][2]] : $makev[$k][2]);
                         }
                         $v = $row[$k];
                      }
                      $cdb->modify_field_value($cdb->tables[$tablename]['fields'][$k], $v);
                      if( $cdb->tables[$tablename]['fields'][$k]['type']=='text' )
	                    {
	                        $cdb->save_text_data($tablename, $k, $privalue, $v);
	                        $v = strlen($v);
	                    }
                      $cdb->tables[$tablename]['datas'][0][$privalue][$k] = $v;
                  }
                  $affect_record++;
	            }
	         }
	      }
	      //高级匹配模式
	      else
	      {
          $conditions = self::make_condition($conditions);
          foreach($cdb->tables[$tablename]['datas'][0] as $pri => $row)
          {
             if( self::compare_row($row, $conditions, $matchmode) )
             {
                foreach($keyvalues as $k => $v)
                {
                    if( !isset( $cdb->tables[$tablename]['fields'][$k] ) )
                    {
                        continue;
                    }
                    if( isset( $row[$k] ) )
                    {
                        if( is_array($v) || empty($v) )
                        {
                            $makev[$k][0] = $makev[$k][1] = '';
                        }
                        else if( !isset($makev[$k][0]) )
                        {
                            $arr = '';
                            preg_match_all("/@([a-z0-9_-]*)@/iU", $v, $arr);
                            $makev[$k][0] = !empty($arr[1]) ? $arr[1] : '';
                            $arr = '';
                            preg_match_all("/@:([\+\-]*)@/iU", $v, $arr);
                            if( isset($arr[1][0]) )
                            {
                               $makev[$k][1] = $arr[1][0];
                               $makev[$k][2] = trim( str_replace($arr[0][0], '', $v) );
                            }
                            else
                            {
                               $makev[$k][1] = $makev[$k][2] = '';
                            }
                        }
                        if( $makev[$k][0] != '' )
                        {
                            foreach($makev[$k][0] as $e)
                            {
                                if(isset($row[$k])) {
                                    $v = str_replace("@{$e}@", $row[$k], $v);
                                }
                            }
                        }
                        else if( $makev[$k][1] != '' )
                        {
                          if( $makev[$k][1]=='--' ) {
                            $row[$k]--;
                          }
                          else if( $makev[$k][1]=='++' ) {
                            $row[$k]++;
                          }
                          else if( $makev[$k][1]=='-' && $makev[$k][2] != '' ) {
                            $row[$k] = $row[$k] - (isset($row[$makev[$k][2]]) ? $row[$makev[$k][2]] : $makev[$k][2]);
                          }
                          else if( $makev[$k][1]=='+' && $makev[$k][2] != '' ) {
                            $row[$k] = $row[$k] + (isset($row[$makev[$k][2]]) ? $row[$makev[$k][2]] : $makev[$k][2]);
                          }
                          $v = $row[$k];
                        }
                        $cdb->modify_field_value($cdb->tables[$tablename]['fields'][$k], $v);
                        $cdb->tables[$tablename]['datas'][0][$pri][$k] = $v;
                    }
                }
                $affect_record++;
             }
          }
        }
        //不保存（用于按不同条件更新多条记录时手动保存数据）
        if( !$issave )
        {
            return $affect_record;
        }
        else
        {
            $rs = $cdb->save_table_datas($tablename, $cdb->tables[$tablename]['datas']);
	          return ($rs ? $affect_record : false);
	      }
    }
    
   /**
	  * 在指定表删除一条或多条记录
	  * @parem $tablename 表名
	  * @parem $conditions  array 查询条件
	  * @parem $matchmode true|false|key
	  * @parem $issave 是否马上保存数据
	  */
	  public static function delete($tablename, $conditions, $matchmode=1, $issave=true)
	  {
	      $affect_record = 0;
	      $cdb = self::factory($tablename);
	      if( $cdb->tables[$tablename]['datas'] === false )
	      {
	         return false;
	      }
	      $prikey = $cdb->tables[$tablename]['key'];
	      //主键匹配模式
	      $newarr = array();
        $c = 0;
	      if( $matchmode==2 )
	      {
	         if( !is_array($conditions) ) {
	            $conditionss[] = $conditions;
	         } else {
	            $conditionss = $conditions;
	         }
	         //把删除项设为空
	         foreach($conditionss as $pri)
           {
              if( isset($cdb->tables[$tablename]['datas'][0][$pri]) )
              {
                  $cdb->tables[$tablename]['datas'][0][$pri] = '';
                  $affect_record++;
              }
           }
           //保留非空数组
           foreach( $cdb->tables[$tablename]['datas'][0] as $k=>$v)
            {
                if($v != '') {
                    $newarr[$k] = $v;
                    $c++;
                }
            }
	      }
	      //高级匹配模式
	      else
	      {
          $conditions = self::make_condition($conditions);
          foreach($cdb->tables[$tablename]['datas'][0] as $pri => $row)
          {
             if( !self::compare_row($row, $conditions, $matchmode) )
             {
                $newarr[$pri] = $row;
             }
             else
             {
                $affect_record++;
             }
          }
        }
        $cdb->tables[$tablename]['datas'][0] = $newarr;
        $cdb->tables[$tablename]['datas'][2] = $cdb->tables[$tablename]['count'] = $c;
        //不保存（用于按不同条件更新多条记录时手动保存数据）
        if( !$issave )
        {
            return $affect_record;
        }
        else
        {
            $rs = $cdb->save_table_datas($tablename, $cdb->tables[$tablename]['datas']);
	          return ($rs ? $affect_record : false);
	      }
    }
    
   /**
    * 马上保存更新过的数据
    */
    public function save_date( $tablename )
    {
        $cdb = self::factory($tablename);
	      if( $cdb->tables[$tablename]['datas'] === false )
	      {
	         return false;
	      }
        if( isset($cdb->tables[$tablename]['datas'][0]) && is_array($cdb->tables[$tablename]['datas'][0]) )
        {
            return $cdb->save_table_datas($tablename, $cdb->tables[$tablename]['datas']);
        }
        else
        {
            return false;
        }
    }
    
	 /**
	  * 设置下一个查询的游标
	  * (不指定的情况下，select 的游标默认被指定为 0 )
	  */
	  public static function set_select_cursor($rsid)
	  {
	     self::$select_cursor = $rsid;
	     self::$select_cursors[$rsid] = 0;
	  }
	  
	 /**
	  * 在指定表选择一条或多条记录
	  * @parem $tablename 表名
	  * @parem $conditions  array 查询条件
	  * @parem $start_pos    页位置
	  * @parem $get_num   获取结果总数（0为不限）
	  * @parem $matchmode 1|0|2
	  * @parem $sortfield = 'primarykey'
	  * @parem $sorttype = 'asc'
	  */
	  public static function select($tablename, $conditions, $start_pos=1, $get_num=0, $matchmode=1, $sortfield='primarykey', $sorttype='asc')
	  {
	      $total_record = 0;
	      $datas = array();
	      $cdb = self::factory($tablename);
	      if( $cdb->tables[$tablename]['datas'] === false )
	      {
	         return false;
	      }
	      
	      //没数据也返回错误
	      if( !isset($cdb->tables[$tablename]['datas'][0]) || !is_array($cdb->tables[$tablename]['datas'][0]) )
	      {
	        return false;
	      }
	      
	      $prikey = $cdb->tables[$tablename]['key'];
	      
	      //匹配数组（只带主键和sort的字段）
	      $match_record = array();
	      
	      if($sortfield=='primarykey' || $sortfield=='')
	      {
	          $sortfield = $prikey;
	      } else {
	          $sortfield = isset($cdb->tables[$tablename]['fields'][$sortfield]) ? $sortfield : $prikey;
	      }
	      
	      //按主键匹配
	      if( $matchmode==2 )
	      {
	         if( !is_array($conditions) ) {
	            $conditionss[] = $conditions;
	         } else {
	            $conditionss = $conditions;
	         }
	         foreach($conditionss as $pri)
           {
              if( isset($cdb->tables[$tablename]['datas'][0][$pri]) )
              {
                  $match_record[$pri] = $cdb->tables[$tablename]['datas'][0][$pri][$sortfield];
                  $total_record++;
              }
           }
	      }
	      //高级匹配模式
	      else
	      {
          $conditions = self::make_condition($conditions);
          foreach($cdb->tables[$tablename]['datas'][0] as $pri => $row)
          {
             if( self::compare_row($row, $conditions, $matchmode) )
             {
                $match_record[$pri] = $cdb->tables[$tablename]['datas'][0][$pri][$sortfield];
                $total_record++;
             }
          }
        }
        
        //对结果进行排序、分页等处理
        //$start_pos=0, $get_num=1, $matchmode=1, $sortfield='primarykey', $sorttype='asc'
        if( $total_record > 0 )
        {
            
            $start = 0;
            $has_num = 0;
            //排序
            if($sortfield == $prikey)
            {
                if( $sorttype=='desc' ) krsort($match_record);
            }
            else
            {
                if( $sorttype=='desc' ) arsort($match_record);
                else asort($match_record);
            }
            //获取数据
            foreach($match_record as $k => $v)
            {
                if( $start >= $start_pos )
                {
                    if( $start >= $start_pos)
                    {
                        
                        $has_num++;
                        $true_row = array();
                        $v = $cdb->tables[$tablename]['datas'][0][$k];
                        foreach( $v as $nk=>$nv )
                        {
                            if( !isset($cdb->tables[$tablename]['fields'][$nk]) )
                            {
                                continue;
                            }
                            if( $cdb->tables[$tablename]['fields'][$nk]['type']=='text' )
                            {
                                $keyid = $cdb->tables[$tablename]['datas'][0][$k][$cdb->tables[$tablename]['key']];
                                $nv = $cdb->get_text_data($tablename, $nk, $keyid);
                            }
                            $true_row[$nk] = $nv;
                        }
                        $datas[] = $true_row;
                        if( $get_num > 0 && $get_num <= $has_num)
                        {
                            break;
                        }
                    }
                }
                $start++;
            }
        }
        //get_text_data($tablename, $field, $keyid)
        self::$select_cursors[ self::$select_cursor ] = $total_record;
        return $datas;
    }
    
   /**
    * 获得上次 insert 的递增 id
    */
    public static function insert_id()
    {
        return self::$insertid;
    }
    
   /**
    * 用指定的主键值获取一条记录
    */
    public static function select_one($tablename, $keyvalue)
	  {
	     $datas = self::select($tablename, array($keyvalue), 0, 1, 2, '', 'asc');
	     return isset($datas[0]) ? $datas[0] : '';
	  }
    
   /**
    * 尝试对简单的sql进行解析，然后返回select的结果
    * @parem $sql sql语句必须为标准的 Select * From tablename where cachedb模式的条件 order by field desc|asc limit start,num;
    * 注： 条件中的 and 和 or 只能出现一种，同时有 and 和 or 的表达式将无效
    * 使用示例：
    * "Select * From catalog where @typename like /9/ and @cid<100 order by cid desc limit 0,5"
    */
    public static function select_sql($sql)
	  {
	     $arr = '';
	     preg_match("/[\t ]{1,}from[\t ]{1,}([a-z0-9_-]*)[\t ]{1,}where[\t ]{1,}(.*)[\t ]{1,}order[\t ]{1,}by[\t ]{1,}([^\t ]*)[\t ]{1,}(desc|asc)[\t ]{1,}limit[\t ]{1,}([0-9,\t ]*)/i", $sql, $arr);
	     if( !isset($arr[5]) )
	     {
	        return self::dispose_error("Sql query Error!");
	     }
	     $tablename  = trim($arr[1]);
	     $conditions = trim($arr[2]);
	     $sortfield = trim($arr[3]);
	     $sorttype = trim($arr[4]);
	     $limit = trim($arr[5]);
	     $matchmode = 1;
	     if( preg_match("/([\t ]{1,})or([\t ]{1,})/i", $conditions) )
	     {
	        $matchmode = 0;
	     }
	     $conditions = preg_replace("/([\t ]{1,})or([\t ]{1,})/i", ' ', $conditions);
	     $conditions = preg_replace("/([\t ]{1,})and([\t ]{1,})/i", ' ', $conditions);
	     $limits = explode(',', $limit);
	     $start_pos = trim($limits[0]);
	     if( !isset($limits[1]) || trim($limits[1])=='' )
	     {
	        $start_pos = 0;
	        $get_num = trim($limits[0]);
	     }
	     else
	     {
	        $get_num = trim($limits[1]);
	     }
	     return self::select($tablename, $conditions, $start_pos, $get_num, $matchmode, $sortfield, $sorttype);
	  }
	  
	  /**
    * 尝试对简单的sql进行解析，然后返回单条结果
    */
    public static function select_sql_one($sql)
	  {
	     if( !preg_match("/limit[\t ]/", $sql) )
	     {
	        $sql .= " limit 0,1 ";
	     }
	     $datas = self::select_sql($sql);
	     return isset($datas[0]) ? $datas[0] : '';
	  }
    
   /**
	  * 获取上一次查询或指定查询的记录总数
	  */
	  public static function select_result_count($rsid=0)
	  {
	     if( isset(self::$select_cursors[$rsid]) )
	     {
	         return self::$select_cursors[$rsid];
	     }
	     else
	     {
	         return false;
	     }
	  }
    
   /**
    * 比较运算
    * @parem &$row
    * @parem &$conditions
    * @parem $matchmode
    */
    public static function compare_row(&$row, &$conditions, $matchmode=1)
    {
        if( empty($conditions) )
        {
            return true;
        }
        foreach($conditions as $condition)
        {
            $fname = $condition[0];
            if($condition[1]=='>')
            {
                 if($matchmode==1 && $row[$fname] <= $condition[2] ) {
                    return false;
                 }
                 if($matchmode==0 && $row[$fname] > $condition[2] ) {
                    return true;
                 }
            }
            else if($condition[1]=='<')
            {
                if($matchmode==1 && $row[$fname] >= $condition[2] ) {
                    return false;
                }
                if($matchmode==0 && $row[$fname] < $condition[2] ) {
                    return true;
                }
            }
            else if($condition[1]=='=')
            {
                if($matchmode==1 && $row[$fname] != $condition[2] ) {
                    return false;
                }
                if($matchmode==0 && $row[$fname] == $condition[2] ) {
                    return true;
                }
            }
            else if($condition[1]=='!=')
            {
                if($matchmode==1 && $row[$fname] == $condition[2] ) {
                    return false;
                }
                if($matchmode==0 && $row[$fname] != $condition[2] ) {
                    return true;
                }
            }
            else if($condition[1]=='like')
            {
                if($matchmode==1 && !preg_match($condition[2], $row[$fname]) ) {
                    return false;
                }
                if($matchmode==0 && preg_match($condition[2], $row[$fname])  ) {
                    return true;
                }
            }
        }
        return ($matchmode==1 ? true : false);
    }
    
   /**
    * 生成查询条件
    * @parem $condition_string
    * 运算条件 >、< 、=、!= 、like(使用like运算符，value应该是完整的perl规则的正则表达式，如 /^a/i )
    * 所有运算式的条件值都不要带 ' 或 " 号，系统是以 @ 识别起始的
    * 如：" @cid > 0 @typename like /abc/i @pid > 0 "
    */
    public static function make_condition( $condition_string )
    {
        $conditions = '';
        $arr = '';
        preg_match_all("/@([a-z0-9_-]*)[ \t]{0,}(>|<|=|!=|like)([^@]++)/iU", $condition_string, $arr);
        foreach($arr[1] as $k=>$v)
        {
            $condition[0] = trim($v);
            $condition[1] = trim($arr[2][$k]);
            $condition[2] = trim($arr[3][$k]);
            if( $condition[0]=='' || $condition[1]=='' || ($condition[2]=='' && $condition[1] != '=') )
            {
                continue;
            }
            $conditions[] = $condition;
        }
        return $conditions;
    }
	  
	 /**
	  * 获得表的记录总数
	  * @parem $tablename
	  */
	  public static function table_count($tablename)
	  {
	     $cdb = self::factory($tablename);
	     if( $cdb->tables[$tablename][1]['count'] == -1 )
	     {
	         $cdb->get_table_datas($tablename);
	     }
	     return $cdb->tables[$tablename][1]['count'];
	  }
	  
	 /**
	  * 修正字段的值
	  * @parem $field_config
	  * @parem $fvalue
	  */
	  public function modify_field_value(&$field_config, &$fvalue)
	  {
	     if( $field_config['type'] == 'varchar' )
	     {
	        $fvalue = stripslashes($fvalue);
	        $slen = intval($field_config['length']) < 500 ? intval($field_config['length']) : 500;
	        if( iconv_strlen($fvalue, 'utf-8') > $slen )
	        {
	            $fvalue = iconv_substr($fvalue, 0, $slen);
	        }
	     }
	     else if( $field_config['type'] == 'int' )
	     {
	        $fvalue = intval($fvalue);
	     }
	     else if( $field_config['type'] == 'float' )
	     {
	        $fvalue = floatval($fvalue);
	     }
	     else if( $field_config['type'] == 'set' )
	     {
	        if( !empty($fvalue) && is_array($fvalue) )
	        {
	            $vs = $fvalue;
	            $fvalue = ',';
	            $svs = explode(',', $field_config['add']);
	            foreach($vs as $v)
	            {
	                $v = stripslashes(trim($v));
	                if( in_array($v, $svs) ) $fvalue .= $v.',';
	            }
	        }
	        else
	        {
	           $fvalue = ''; 
	        }
	     }
	  }
	  
	 /**
    * 是否存在某表
    * @parem $tablename
    */
    public function is_table($tablename)
    {
        return isset($this->tables[$tablename]);
    }
    
    /**
    * 清空某表
    * @parem $tablename
    */
    public function clear_table($tablename)
    {
        $table_file = CACHE_DB.'/'.$tablename.'.dat';
        if( file_exists($table_file) )
        {
            unlink($table_file);
        }
    }
    
   /**
    * 获得某表的所有数据
    * @parem $tablename
    */
    public function get_table_datas($tablename)
    {
        if( !isset($this->tables[$tablename]) )
        {
            return self::dispose_error( "Table:{$tablename} is not config!" );
        }
        if( isset(self::$cdb->tables[$tablename]['datas'][0]) )
        {
            return self::$cdb->tables[$tablename]['datas'];
        }
        $table_file = CACHE_DB.'/'.$tablename.'.dat';
        if( file_exists($table_file) )
        {
            $dat = file_get_contents($table_file);
            $arr = unserialize($dat);
            $this->tables[$tablename]['count'] = $arr[2];
            $this->tables[$tablename]['datas'] = $arr;
            return $this->tables[$tablename]['datas'];
        }
        else
        {
            $this->tables[$tablename]['datas'] = '';
            $this->tables[$tablename]['count'] = 0;
            return '';
        }
    }
    
    /**
    * 保存某表的数据
    * @parem $tablename
    * @parem $datas
    */
    public function save_table_datas($tablename, &$datas)
    {
        if( !isset($this->tables[$tablename]) )
        {
            return self::dispose_error( "Table:{$tablename} is not config!" );
        }
        $table_file = CACHE_DB.'/'.$tablename.'.dat';
        $dat = serialize($datas);
        ignore_user_abort(true);
        $fp = fopen($table_file, 'w');
        if( flock($fp, LOCK_EX) )
        {
            fwrite($fp, $dat);
            flock($fp, LOCK_UN);
        }
        else
        {
            file_put_contents($table_file.'.tmp', $dat);
            return self::dispose_error("Couldn't lock the table: {$tablename} file!");
        }
        fclose($fp);
        ignore_user_abort(false);
        return true;
    }
    
    /**
    * 保存某表的文本数据
    * @parem $tablename
    * @param $field
    * @param $keyid 主键ID
    * @parem $str
    */
    public function save_text_data($tablename, $field, $keyid, &$str)
    {
        if( !isset($this->tables[$tablename]) )
        {
            return self::dispose_error( "Table:{$tablename} is not config!" );
        }
        $target_dir = CACHE_DB.'/text/'.$tablename;
        if( !is_dir($target_dir) )
        {
            mkdir($target_dir, 0777);
        }
        $iddir = ceil($keyid/1000);
        if( !is_dir($target_dir.'/'.$iddir) )
        {
            mkdir($target_dir.'/'.$iddir, 0777);
        }
        $target_file = $target_dir.'/'.$iddir.'/'.$field.'-'.$keyid.'.txt';
        return file_put_contents($target_file, stripslashes($str));
    }
    
   /**
    * 获得某表的文本数据
    * @parem $tablename
    * @param $field
    * @param $keyid 主键ID
    */
    public function get_text_data($tablename, $field, $keyid)
    {
        if( !isset($this->tables[$tablename]) )
        {
            return self::dispose_error( "Table:{$tablename} is not config!" );
        }
        $target_dir = CACHE_DB.'/text/'.$tablename;
        $iddir = ceil($keyid/1000);
        $target_file = $target_dir.'/'.$iddir.'/'.$field.'-'.$keyid.'.txt';
        if( file_exists($target_file) )
        {
            return file_get_contents($target_file);
        }
        else
        {
            return '';
        }
    }

   /**
    * 获取指定表的信息
    * @parem $tablename
    */
    public function get_table_infos($tablename)
    {
        return isset($this->tables[$tablename]) ? $this->tables[$tablename] : false;
    }
	  
	 /**
    * 获取基本配置信息
    * $tables = array(0=>fields, 1=>table propertys)
    */
    private function analyse_config()
    {
        $infofile = CACHE_DB.'/tables.config';
        if( !file_exists($infofile) ) {
            return ;
        }
        $configstr = file_get_contents($infofile);
        $arr = '';
        preg_match_all("/<table name=\"([^\">]*)\">(.+)<\/table>/isU", $configstr, $arr);
        if( isset($arr[1]) && isset($arr[2]) )
        {
            foreach($arr[1] as $k=>$tablename)
            {
                $tablename = trim($tablename);
                if( $tablename=='' ) {
                    continue;
                }
                if( !empty($arr[2][$k]) )
                {
                    $narr = '';
                    preg_match_all("/<field([^>]*)\/>/isU", $arr[2][$k], $narr);
                    if( isset($narr[1]) )
                    {
                        foreach($narr[1] as $att)
                        {
                            $patts = '';
                            preg_match_all("/([0-9a-z_-]*)[\t ]{0,}=[\t ]{0,}[\"']([^>\"']*)[\"']/isU", $att, $patts);
                            if( !isset($patts[1]) ) {
                               continue;
                            }
                            $atts = array();
                            foreach($patts[1] as $ak=>$attname)
                            {
                                $atts[trim($attname)] = trim($patts[2][$ak]);
                            }
                            if( empty($atts['name']) ) {
                                continue;
                            }
                            $this->tables[$tablename]['fields'][$atts['name']]['type'] = isset($atts['type']) ? $atts['type'] : 'varchar';
                            $this->tables[$tablename]['fields'][$atts['name']]['length'] = isset($atts['length']) ? $atts['length'] : '30';
                            $this->tables[$tablename]['fields'][$atts['name']]['default'] = isset($atts['default']) ? $atts['default'] : '';
                            $this->tables[$tablename]['fields'][$atts['name']]['add'] = isset($atts['add']) ? $atts['add'] : '';
                            if($this->tables[$tablename]['fields'][$atts['name']]['add']=='primarykey')
                            {
                                $this->tables[$tablename]['key'] = $atts['name'];
                            }
                        }
                        //强制使用主键字段
                        if( !isset($this->tables[$tablename]['key']) )
                        {
                            $this->tables[$tablename]['key'] = 'cdbid';
                            $this->tables[$tablename]['fields']['cdbid']['type'] = 'int';
                            $this->tables[$tablename]['fields']['cdbid']['length'] = 4;
                            $this->tables[$tablename]['fields']['cdbid']['default'] = '';
                            $this->tables[$tablename]['fields']['cdbid']['add'] = 'primarykey';
                        }
                        //记录总数（默认不统计）
                        $this->tables[$tablename]['count'] = -1;
                        //默认数据
                        $this->tables[$tablename]['datas'] = '';
                    }
                    
                }
            }
        }
    }

   /**
    * 获得一组分页数据
    * @parem $sql
    * @parem $pagesize
    * @parem $page_no
    * @parem $keyword
    * @parem $orderby='' 排序字段
    * @parem $url=''  网址前缀
    * @parem $sorttype='desc' 排序方式
    */
    public static function get_datalist($sql, $pagesize, $page_no, $keyword, $orderby='', $url='', $sorttype='desc')
    {
        
        //keyword
        if( $keyword != '' )
        {
            $url .= "&keyword=".urlencode(stripslashes($keyword));
        }
        
        $start = ($page_no - 1) * $pagesize;
        //$prikey = $cdb->tables[$tablename]['key'];
        
        //order by 条件
        $orderquery = '';
        if( empty($orderby) ) {
            $orderby = 'primarykey';
        }
        $orderquery = "order by {$orderby} {$sorttype}";
        
        //$url .= "&orderby={$orderby}";
        
        //limit
        $limitquery = " limit {$start},{$pagesize} ";
        
        $datas['url'] = $url;
        
        $datas['data'] = self::select_sql($sql." $orderquery $limitquery ");
        
        $datas['total'] = self::select_result_count();
        
        $datas['pagination'] = cls_cache_db::pagination( $pagesize, $page_no, $datas['total'], $url );
        
        return $datas;
    }  
    
   /**
    * 获得分页符列表
    * @return string
    */
    public static function pagination( $page_size, $page_no, $all_count = 0, $url='' )
    {
        
       if( empty( $all_count ) )
       {
           return '';
       }
       (int) $page_size > 0 or die(__FUNCTION__ . ':$page_size 小于或者等于0!');
       (int) $page_no > 0 or die(__FUNCTION__ . ':$page_no 小于或者等于0!');
       is_numeric($all_count) or die(__FUNCTION__ . ':$all_count 必须为数字!');

       $page_count = ceil($all_count / $page_size);
       
       if( $all_count==0 )
       {
           return "<div class=\"page-list\">无任何记录！</div>";
       }
       else if($page_count == 1)
       {
           return "<div class=\"page-list\">共 1 页 / {$all_count} 条记录</div>";
       }

       if( $url=='' )
       {
         $url = preg_replace('/\?page_no=\d+/', '?', $_SERVER['REQUEST_URI']);
         $url = preg_replace('/&page_no=\d+/', '', $url);
       }
       
       //$get_string = strrpos($url, '?') ? $url . "&page_no" : $url . "?page_no";
       $get_string = $url . "&page_no" ;

       /* 分页样式1 */
       $result = '<div class="page-list">';
       $result .= ($page_no <= 1) ? '&lt;&lt;上一页 ' : '<a href="' . $get_string . '=' . ($page_no - 1) . '">&lt;&lt;上一页</a> ';
       for ($i = ($page_no - 6); $i <= ($page_no + 6); $i ++)
       {
           if ($i > 0 && $i <= $page_count)
           {
               $result .= ($i == $page_no) ? '<strong class="m5">' . $i . '</strong> ' : "<a class='page-item' href='$get_string=$i'>$i</a> ";
           }
       }

       $result .= ($page_no >= $page_count) ? '下一页&gt;&gt;' : "<a href='$get_string=" . ($page_no + 1) . "'>下一页&gt;&gt;</a>";
       $result .= '</div>';

       /* 分页样式2 */
       $result2 = ($page_no <= 1) ? '<span class="nextprev">&laquo; 上一页</span> ' : '<a class="nextprev" href="' . $get_string . '=' . ($page_no - 1) . '">&laquo; 上一页</a> ';
       for ($i = ($page_no - 5); $i <= ($page_no + 5); $i ++)
       {
           if ($i > 0 && $i <= $page_count)
           {
                $result2 .= ($i == $page_no) ? '<span class="current">' . $i . '</span> ' : "<a href='$get_string=$i'>$i</a> ";
           }
       }

       $result2 .= ($page_no >= $page_count) ? '<span class="nextprev">下一页 &raquo;</span> ' : "<a class='nextprev' href='$get_string=" . ($page_no + 1) . "'>下一页 &raquo;</a> ";

       //$result = preg_replace("/(index.php)|(^[\/]{1,})/i" ,'', $result);

       return $result2;
    }
    
   /**
    * 显示错误
    */
    public static function dispose_error($msg)
    {
        if( self::$show_error )
        {
            exit("CACHE_DB ".$msg);
        }
        else
        {
            return false;
        }
    }
}
?>