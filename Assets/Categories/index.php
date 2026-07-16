<?php

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>
      <Error>
        <Code>AccessDenied</Code>
        <Message>Access Denied</Message>
        <Status>403 Forbidden</Status>
        <Id>" . base64_encode("ASSETS-CATEGORIES") . "</Id>
      </Error> ";
