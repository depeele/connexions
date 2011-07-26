#!/bin/sh
keyLen=1024

# Create a CA key and certificate
if [ ! -f ca.key ]; then
    echo ">>> Generating CA key";
    openssl genrsa -des3 -out ca.key ${keyLen}
fi

if [ ! -f ca.crt ]; then
    echo ">>> Generating CA certificate"
    openssl req -new -x509 -days 365 -key ca.key -out ca.crt
fi

# Create a server key and certificate
if [ ! -f server.key ]; then
    echo ">>> Generating Server key"
    openssl genrsa -des3 -out server.key ${keyLen}
fi

if [ ! -f server.csr ]; then
    echo ">>> Generating Server request"
    openssl req -new -key server.key -out server.csr
fi

if [ ! -f server.crt ]; then
    echo ">>> Generating/Signing server certificate"
    openssl x509 -req -days 365 -in server.csr -CA ca.crt -CAkey ca.key -set_serial 01 -out server.crt
fi

if [ ! -f server-secure.key ]; then
    echo ">>> Remove password from server key"
    openssl rsa -in server.key -out server-insecure.key
    mv server.key server-secure.key
    mv server-insecure.key server.key
fi

# Create a client key and certificate
if [ ! -f client.key ]; then
    echo ">>> Generating Client key"
    openssl genrsa -des3 -out client.key ${keyLen}
fi

if [ ! -f client.csr ]; then
    echo ">>> Generating Client request"
    openssl req -new -key client.key -out client.csr
fi

if [ ! -f client.crt ]; then
    echo ">>> Generating/Signing client certificate"
    openssl x509 -req -days 365 -in client.csr -CA ca.crt -CAkey ca.key -set_serial 02 -out client.crt
fi

if [ ! -f client.p12 ]; then
    echo ">>> Generating client PKCS12 file"
    openssl pkcs12 -export -in client.crt -inkey client.key -certfile ca.crt -name "Test Client Cert" -out client.p12
fi

echo "========================================================="
echo ">>> Ensure that apache.conf is applied to the SSL server"
echo ">>> and certificates and keys are in place:"
echo ">>>   /etc/ssl/ca-bundle.crt      => ca.crt"
echo ">>>   /etc/ssl/certs/Test.pem     => server.crt"
echo ">>>   /etc/ssl/private/Test.key   => server.key"
