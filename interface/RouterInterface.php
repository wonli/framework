<?php
interface RouterInterface
{
	function getController();
	function getAction();
	function getParams();
}