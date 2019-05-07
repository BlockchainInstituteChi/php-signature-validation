<?php
	require __DIR__ . '/vendor/autoload.php';

	// use Mdanter\Ecc\Crypto\Signature\SignHasher;
	// use Mdanter\Ecc\EccFactory;
	// use Mdanter\Ecc\Curves\CurveFactory;
	// use Mdanter\Ecc\Curves\SecgCurve;
	// use Mdanter\Ecc\Math\GmpMathInterface;

	// use kornrunner\Secp256k1;
	// use kornrunner\Signature\Signature as kSig;

	use Blockchaininstitute\jwtTools as jwtTools;

	echo "\r\nStarting validate.php \r\n";

	$jwtTools = new jwtTools();

	// $jwt = "eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NksifQ.eyJpYXQiOjE1NTY4MjE3ODQsInJlcXVlc3RlZCI6WyJuYW1lIl0sImNhbGxiYWNrIjoiaHR0cHM6Ly9jaGFzcXVpLnVwb3J0Lm1lL2FwaS92MS90b3BpYy9jSENnWHpnRG1hdEhINE9SIiwibmV0IjoiMHg0IiwidHlwZSI6InNoYXJlUmVxIiwiaXNzIjoiMm9qRXRVWEJLMko3NWVDQmF6ejR0bmNFV0UxOG9GV3JuZkoifQ.a99f1734d72bead02625295ed5178e1c85807ac6dd49205d3406a26409b45a816f7a534209d72739addfd33136f2de61353b0eb84be45d40d9a3f3f460a1a705";

	// $jwt = "eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NksifQ.eyJpYXQiOjE1NTYyMTQ5MzcsImV4cCI6MTU1NjMwMTMzNywiYXVkIjoiMm9qRXRVWEJLMko3NWVDQmF6ejR0bmNFV0UxOG9GV3JuZkoiLCJ0eXBlIjoic2hhcmVSZXNwIiwibmFkIjoiMm90MWhDdVZBTDZuUTNOUXJ5amtCQVJHdHNqNHJzYW81NzUiLCJvd24iOnsibmFtZSI6IkFsZXgifSwicmVxIjoiZXlKMGVYQWlPaUpLVjFRaUxDSmhiR2NpT2lKRlV6STFOa3NpZlEuZXlKcFlYUWlPakUxTlRZeU1UUTVNak1zSW5KbGNYVmxjM1JsWkNJNld5SnVZVzFsSWwwc0ltTmhiR3hpWVdOcklqb2lhSFIwY0hNNkx5OWphR0Z6Y1hWcExuVndiM0owTG0xbEwyRndhUzkyTVM5MGIzQnBZeTkwTUVsVmNtcEdjVEIzTjNkMlVsWnVJaXdpYm1WMElqb2lNSGcwSWl3aWRIbHdaU0k2SW5Ob1lYSmxVbVZ4SWl3aWFYTnpJam9pTW05cVJYUlZXRUpMTWtvM05XVkRRbUY2ZWpSMGJtTkZWMFV4T0c5R1YzSnVaa29pZlEuWTVtMTFKZmR1UG9hNW1fdm4zYkI4TUlqTHktUWdETHI3YTVMREhJcjgxclBkQWVrcmNKTzJra2UxQmJOOVVaSlVrNUQzZzVCRldqNW81RHM4cWQ0bUEiLCJpc3MiOiIyb3QxaEN1VkFMNm5RM05Rcnlqa0JBUkd0c2o0cnNhbzU3NSJ9.dhS6KNpA21NJUmxtNmOCBv8ewBIwyOgqak9eXpUKZS8Hk-zpxjbbnkhLaOVHCENFjK2zzm9OxVekgGlwlNoIbw";

	$jwt = "eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NksifQ.eyJpYXQiOjE1NTY5MTI4MzMsInJlcXVlc3RlZCI6WyJuYW1lIl0sImNhbGxiYWNrIjoiaHR0cHM6Ly9jaGFzcXVpLnVwb3J0Lm1lL2FwaS92MS90b3BpYy8xT3pTalFSRnJGOTQ4TExrIiwibmV0IjoiMHg0IiwidHlwZSI6InNoYXJlUmVxIiwiaXNzIjoiMm9qRXRVWEJLMko3NWVDQmF6ejR0bmNFV0UxOG9GV3JuZkoifQ.NzllNDdiNDE3MWQ5Y2E3NTllODZkOTdiNDJjMmRiMTUyNTIwN2EwYjlkNmFiMWIzYjkzZDk4YTg0NTA1M2QxNTA4ZGQ1Mzg5YzAxNTRiZmI2Y2YwZDNmNDQzY2Y3OTA5NWZiYjExNzY5NjcwNzQ3ZDAwMzRhNzQ1Y2Y2YzM4NTA";

	$isVerified = $jwtTools->verifyJWT2($jwt);

	echo "\r\n\r\nisVerified:\r\n" , $isVerified;

	echo "\r\n\r\n";