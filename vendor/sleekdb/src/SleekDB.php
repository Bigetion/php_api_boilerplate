<?php

  namespace SleekDB;

  require_once 'vendor/sleekdb/traits/helpers.php';
  require_once 'vendor/sleekdb/traits/conditions.php';
  require_once 'vendor/sleekdb/traits/caching.php';

  class SleekDB {

    use \HelpersTrait, \ConditionsTrait, \CacheTraits;

    // Initialize the store.
    function __construct( $storeName = false ) {
      $this->init( $storeName );
    }

    // Read store objects.
    public function fetch($keys=array()) {
      // Check if data should be provided from the cache.
     return $this->findStore($keys); // Returns data without looking for cached data.
    }

    // Creates a new object in the store.
    // The object is a plaintext JSON document.
    public function insert( $storeData = false ) {
      // Handle invalid data
      if ( ! $storeData OR empty( $storeData ) ) show_error( 'SleekDB Error', 'No data found to store' );
      // Make sure that the data is an array
      if ( ! is_array( $storeData ) ) show_error( 'SleekDB Error', 'Storable data must an array' );
      $storeData = $this->writeInStore( $storeData );
      // Check do we need to wipe the cache for this store.
      if ( $this->deleteCacheOnCreate === true ) {
        $this->_emptyAllCache();
      }
      return $storeData;
    }

    // Creates multiple objects in the store.
    public function insertMany( $storeData = false ) {
      // Handle invalid data
      if ( ! $storeData OR empty( $storeData ) ) show_error( 'SleekDB Error', 'No data found to store' );
      // Make sure that the data is an array
      if ( ! is_array( $storeData ) ) show_error( 'SleekDB Error', 'Storable data must an array' );
      // All results.
      $results = [];
      foreach ( $storeData as $key => $node ) {
        $results[] = $this->writeInStore( $node );
      }
      return $results;
    }

    // Updates matched store objects.
    public function update( $updateable,  $objects = array()) {
      // Find all store objects.
      $storeObjects = $this->findStore();

      if(count($objects) > 0) $storeObjects = $objects;
      // If no store object found then return an empty array.
      if ( empty( $storeObjects ) ) return false;
      foreach ( $storeObjects as $data ) {
        $newData = array_merge(array(), $data);
        foreach ( $updateable as $key => $value ) {
          // Do not update the _id reserved index of a store.
          if( $key != '_id' && $key != '_hash' ) {
            $newData[ $key ] = $value;
          }
        }
        $storePath = $this->storeName . '/data/' . $data[ '_id' ] . '.json';
        if ( file_exists( $storePath ) ) {
          unset($newData['_hash']);
          unset($newData['_id']);
          $newData['_hash'] = md5(json_encode( $newData ));
          $newData['_id'] = $data['_id'];
          file_put_contents( $storePath, json_encode( $newData ) );
        }
      }
      return true;
    }

    // Deletes matched store objects.
    public function delete($objects = array()) {
      // Find all store objects.
      $storeObjects = $this->findStore();

      if(count($objects) > 0) $storeObjects = $objects;
      if ( ! empty( $storeObjects ) ) {
        foreach ( $storeObjects as $data ) {
          if ( ! unlink( $this->storeName . '/data/' . $data[ '_id' ] . '.json' ) ) {
            show_error( 'SleekDB Error', 
              'Unable to delete storage file! 
              Location: "'.$this->storeName . '/data/' . $data[ '_id' ] . '.json'.'"' 
            );
          }

        }
        $this->conditions = array();
        return true;
      } else {
        // Nothing found to delete
        show_error( 'SleekDB Error', 'Invalid store object found, nothing to delete.' );
      }
    }

  }