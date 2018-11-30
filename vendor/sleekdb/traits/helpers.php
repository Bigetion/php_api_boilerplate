<?php

  /**
   * Collections of method that helps to manage the data.
   * All methods in this trait should be private.
   * 
   */
  trait HelpersTrait {

    // Initialize data that SleekDB required to operate.
    private function init( $storeName, $config=array(
        'storeLocation' => 'application/db/',
        'enableAutoCache' => false,
        'timeOut' => 120,
    )) {
      if ( ! $storeName OR empty( $storeName ) ) show_error( 'SleekDB Error', 'Invalid store name provided' );
      // Set timeout.
      set_time_limit( $config[ 'timeOut' ] );
      // Define the store path
      
      // Validate the directory path.
      $storeLocation = trim( $config[ 'storeLocation' ] );

      // Handle the directory path ending.
      if ( substr( $storeLocation, -1 ) !== '/' ) $storeLocation += '/';
      
      $this->storeName = $storeLocation . $storeName;

      if (!is_dir($this->storeName)) {
        mkdir($this->storeName, 0777, true);
        chmod($this->storeName, 0777);
      }
      if (!is_dir($this->storeName.'/data')) {
        mkdir($this->storeName.'/data', 0777, true);
        chmod($this->storeName.'/data', 0777);
      }
      if (!is_dir($this->storeName.'/cache')) {
        mkdir($this->storeName.'/cache', 0777, true);
        chmod($this->storeName.'/cache', 0777);
      }

      // Set empty results
      $this->results = [];
      // Set a default limit
      $this->limit = 0;
      // Set a default skip
      $this->skip = 0;
      // Set default conditions
      $this->conditions = [];
      // Set default group by value
      $this->orderBy = [
        'order' => false,
        'field' => '_id'
      ];
      // Set the default search keyword as an empty string.
      $this->searchKeyword = '';
      // Disable make cache by default.
      $this->makeCache = false;
      // Descide the cache status.
      if ( $config[ 'enableAutoCache' ] === true ) {
        $this->useCache = true;
        // A flag that is used to check if cache should be empty 
        // while create a new object in a store.
        $this->deleteCacheOnCreate = true; 
      } else {
        $this->useCache = false;
        // A flag that is used to check if cache should be empty 
        // while create a new object in a store.
        $this->deleteCacheOnCreate = false; 
      }      
    }
    
    // Returns a new and unique store object ID, by calling this method it would also
    // increment the ID system-wide only for the store.
    private function getStoreId() {
      $counterPath = $this->storeName. '/counter.sdb';
      if ( file_exists( $counterPath ) ) {
        $counter = (int) file_get_contents( $counterPath );
      } else {
        $counter = 0;
      }
      $counter++;
      file_put_contents( $counterPath, $counter );
      return $counter;
    }

    // Return the last created store object ID.
    private function getLastStoreId() {
      $counterPath = $this->storeName. '/counter.sdb';
      if ( file_exists( $counterPath ) ) {
        return (int) file_get_contents( $counterPath );
      } else {
        return 0;
      }
    }

    // Get a store by its system id. "_id"
    private function getStoreById( $id ) {
      $store = $this->storeName . '/data/' . $id . '.json';
      if ( file_exists( $store ) ) {
        $data = json_decode( file_get_contents( $store ), true );
        if ( $data !== false ) return $data;
      }
      return [];
    }

    function getDataWithKeys($keys, $data) {
      if(count($keys)>0) {
        $newData = array();
        foreach($keys as $col) {
          if(array_key_exists($col, $data)){
            $newData[$col] = $data[$col];
          }
        }
        return $newData;
      }
      return $data;
    }

    // Find store objects with conditions, sorting order, skip and limits.
    private function findStore($keys=array()) {
      $found          = [];
      $lastStoreId    = $this->getLastStoreId();
      $searchRank     = [];

      // Start collecting and filtering data.
      for ( $i = 0; $i <= $lastStoreId; $i++ ) {
        // Collect data of current iteration.
        $data = $this->getStoreById( $i );
        if ( ! empty( $data ) ) {
          // Filter data found.
          if ( empty( $this->conditions ) ) {
            // Append all data of this store.
            $found[] = $this->getDataWithKeys($keys, $data);
          } else {
            // Append only passed data from this store.
            $storePassed = true;
            // Iterate each conditions.
            foreach ( $this->conditions as $condition ) {
              // Check for valid data from data source.
              $validData = true;
              $fieldValue = '';
              try {
                $fieldValue = $this->getNestedProperty( $condition[ 'fieldName' ], $data );
              } catch( Exception $e ) {
                $validData   = false;
                $storePassed = false;
              }
              if( $validData === true ) {
                // Check the type of rule.
                if ( $condition[ 'condition' ] === '=' ) {
                  // Check equal.
                  if ( $fieldValue != $condition[ 'value' ] ) $storePassed = false;
                } else if ( $condition[ 'condition' ] === '!=' ) {
                  // Check not equal.
                  if ( $fieldValue == $condition[ 'value' ] ) $storePassed = false;
                } else if ( $condition[ 'condition' ] === '>' ) {
                  // Check greater than.
                  if ( $fieldValue <= $condition[ 'value' ] ) $storePassed = false;
                } else if ( $condition[ 'condition' ] === '>=' ) {
                  // Check greater equal.
                  if ( $fieldValue < $condition[ 'value' ] ) $storePassed = false;
                } else if ( $condition[ 'condition' ] === '<' ) {
                  // Check less than.
                  if ( $fieldValue >= $condition[ 'value' ] ) $storePassed = false;
                } else if ( $condition[ 'condition' ] === '<=' ) {
                  // Check less equal.
                  if ( $fieldValue > $condition[ 'value' ] ) $storePassed = false;
                }
              }
            }
            // Check if current store is updatable or not.
            if ( $storePassed === true ) {
              // Append data to the found array.
              $found[] = $this->getDataWithKeys($keys, $data);
            }
          }
        }
      }
      if ( count( $found ) > 0 ) {
        // Check do we need to sort the data.
        if ( $this->orderBy[ 'order' ] !== false ) {
          // Start sorting on all data.
          $found = $this->sortArray( $this->orderBy[ 'field' ], $found, $this->orderBy[ 'order' ] );
        }
        // If there was text search then we would also sort the result by search ranking.
        if ( ! empty( $this->searchKeyword ) ) {
          $found = $this->performSerach( $found );
        }
        // Skip data
        if ( $this->skip > 0 ) $found = array_slice( $found, $this->skip );
        // Limit data.
        if ( $this->limit > 0 ) $found = array_slice( $found, 0, $this->limit );
      }
      return $found;
    }

    // Writes an object in a store.
    private function writeInStore( $storeData ) {
      // Cast to array
      $storeData = (array) $storeData;
      // Check if it has _id key
      if ( isset( $storeData[ '_id' ] ) ) show_error( 'SleekDB Error', 'The _id index is reserved by SleekDB, please delete the _id key and try again' );
      $id = $this->getStoreId();
      // Add the system ID with the store data array.
      $storeData[ '_hash' ] = md5(json_encode($storeData));
      $storeData[ '_id' ] = $id;
      // Prepare storable data
      $storableJSON = json_encode( $storeData );
      if ( $storableJSON === false ) show_error( 'SleekDB Error', 'Unable to encode the data array, 
        please provide a valid PHP associative array' );
      // Define the store path
      $storePath = $this->storeName . '/data/' . $id . '.json';
      if ( ! file_put_contents( $storePath, $storableJSON ) ) {
        show_error( 'SleekDB Error', "Unable to write the object file! Please check if PHP has write permission." );
      }
      return $storeData;
    }

    // Sort store objects.
    private function sortArray( $field, $data, $order = 'ASC' ) {
      $dryData = [];
      // Check if data is an array.
      if( is_array( $data ) ) {
        // Get value of the target field.
        foreach ( $data as $value ) {
          $dryData[] = $this->getNestedProperty( $field, $value );
        }
      }
      // Descide the order direction.
      if ( strtolower( $order ) === 'asc' ) asort( $dryData );
      else if ( strtolower( $order ) === 'desc' ) arsort( $dryData );
      // Re arrange the array.
      $finalArray = [];
      foreach ( $dryData as $key => $value) {
        $finalArray[] = $data[ $key ];
      }
      return $finalArray;
    }

    // Get nested properties of a store object.
    private function getNestedProperty( $field = '', $data ) {
      if( is_array( $data ) AND ! empty( $field ) ) {
        // Dive deep step by step.
        foreach( explode( '.', $field ) as $i ) {
          // If the field do not exists then insert an empty string.
          if ( ! isset( $data[ $i ] ) ) {
            $data = '';
            show_error( 'SleekDB Error', '"'.$i.'" index was not found in the provided data array' );
            break;
          } else {
            // The index is valid, collect the data.
            $data = $data[ $i ];
          }
        }
        return $data;
      }
    }

    // Do a sesrch in store objects. This is like a doing a fulltext search.
    private function performSerach( $data = [] ) {
      if ( empty( $data ) ) return $data;
      $nodesRank = [];
      // Looping on each store data.
      foreach ($data as $key => $value) {
        // Looping on each field name of search-able fields.
        foreach ($this->searchKeyword[ 'field' ] as $field) {
          try {
            $nodeValue = $this->getNestedProperty( $field, $value );
            // The searchable field was found, do comparison against search keyword.
            similar_text( $nodeValue, $this->searchKeyword['keyword'], $perc );
            if ( $perc > 50 ) {
              // Check if current store object already has a value, if so then add the new value.
              if ( isset( $nodesRank[ $key ] ) ) $nodesRank[ $key ] += $perc;
              else $nodesRank[ $key ] = $perc;
            }
          } catch ( Exception $e ) {
            continue;
          }
        }
      }
      if ( empty( $nodesRank ) ) {
        // No matched store was found against the search keyword.
        return [];
      }
      // Sort nodes in descending order by the rank.
      arsort( $nodesRank );
      // Map original nodes by the rank.
      $nodes = [];
      foreach ($nodesRank as $key => $value) {
        $nodes[] = $data[$key];
      }
      return $nodes;
    }
    
  }
  