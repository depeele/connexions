ZendFramework-1.10.5 with modifications indicated by the following patches.

Apply these patches via:
    patch -p0 < README.txt

===============================================================================
--- ZendFramework-1.10.5/library/Zend/Db/Adapter/Abstract.php	2010-01-05 21:05:09.000000000 -0500
+++ Db/Adapter/Abstract.php	2010-06-22 08:25:33.000000000 -0400
@@ -843,7 +843,17 @@
             foreach ($value as &$val) {
                 $val = $this->quote($val, $type);
             }
-            return implode(', ', $value);
+            // :XXX: patch: Enclose any imploded array in parenthesis. {
+            //       This allows multi-key values that should be formatted as:
+            //          ((a,b,...), (c,d,...), (e,f,...), ...)
+            //
+            //       Note: This requires an SQL syntax of
+            //                  ' IN ?'   instead of
+            //                  ' IN (?)'
+            //
+            return '('. implode(', ', $value) .')';
+            //return implode(', ', $value);
+            // :XXX: patch: }
         }
 
         if ($type !== null && array_key_exists($type = strtoupper($type), $this->_numericDataTypes)) {

===============================================================================
Json/Server/Response.php
--- ZendFramework-1.10.5/library/Zend/Json/Server/Response.php	2010-01-05 21:05:09.000000000 -0500
+++ Json/Server/Response.php	2010-06-10 13:22:30.000000000 -0400
@@ -178,8 +178,19 @@
                 'id'     => $this->getId(),
             );
         } else {
+            // :XXX: patch: if the result can be simplified, do it {
+            $result   = $this->getResult();
+            if (is_object($result))
+            {
+                if (method_exists($result, 'toArray'))
+                    $result = $result->toArray();
+                else if (method_exists($result, '__toString'))
+                    $result = $result->__toString();
+            }
+            // :XXX: patch: }
+
             $response = array(
-                'result' => $this->getResult(),
+                'result' => $result,
                 'id'     => $this->getId(),
                 'error'  => null,
             );

===============================================================================
OpenId/Consumer.php
--- ZendFramework-1.10.5/library/Zend/OpenId/Consumer.php	2010-01-05 21:05:09.000000000 -0500
+++ OpenId/Consumer.php	2010-06-22 08:08:20.000000000 -0400
@@ -761,6 +761,13 @@
                 $r)) {
             $version = 1.1;
             $server = $r[2];
+        /* :XXX: Based upon OpenId-2.0 patch:
+         *          http://framework.zend.com/issues/browse/ZF-6905 {
+         */
+        } else if (preg_match('/<URI>([^<]+)<\/URI>/i', $response, $r)) {
+            $version = 2.0;
+            $server = $r[1];
+        // :XXX: }
         } else {
             return false;
         }
@@ -848,6 +855,15 @@
         if ($version >= 2.0) {
             $params['openid.ns'] = Zend_OpenId::NS_2_0;
         }
+        /* :XXX: Based upon OpenId-2.0 patch:
+         *          http://framework.zend.com/issues/browse/ZF-6905 {
+         */
+        if (($version <= 2.0) &&
+            ($server == 'https://www.google.com/accounts/o8/ud')) {
+            $id        = 'http://specs.openid.net/auth/2.0/identifier_select';
+            $claimedId = $id;
+        }
+        // :XXX: }
 
         $params['openid.mode'] = $immediate ?
             'checkid_immediate' : 'checkid_setup';

