<?php

declare(strict_types=1);

class PID_Controller extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Variables
        $this->RegisterVariableFloat("PIDOutputValue", "Output Value 0-100", "");


        //Poperties
        $this->RegisterPropertyInteger("TargetVariableID", 0);
        $this->RegisterPropertyInteger("ActualVariableID", 0);
        $this->RegisterPropertyInteger("AverageInputCount", 0);
        $this->RegisterPropertyInteger("OutputVariableID", 0);

        $this->RegisterPropertyFloat("PFaktor", 1);
        $this->RegisterPropertyFloat("IFaktor", 0);
        $this->RegisterPropertyFloat("IntegrationTime", 1);
        $this->RegisterPropertyBoolean("IntegrationMethode", true);

        $this->RegisterPropertyFloat("DFaktor", 0);
        $this->RegisterPropertyFloat("Scale", 3);
        $this->RegisterPropertyInteger("UpdateThres", 0);
        $this->RegisterPropertyInteger("RecalcInterval", 0);
    //    $this->RegisterPropertyBoolean("Invert", false);


        //Register Output Script
        $this->RegisterPropertyInteger("OutScriptID", 0);

        //Attributes
        $this->RegisterAttributeFloat("PrevErr", 0);
        $this->RegisterAttributeFloat("SummErr", 0);
        $this->RegisterAttributeFloat("PrevOutput", 0);
        $this->RegisterAttributeInteger("PrevTimestamp", 0);

        //Timers
        $this->RegisterTimer('ReCalc', 0, "PID_UpdateOutputValue(\$_IPS['TARGET']);");

        //Actions
        #$this->RequestAction('UpdateOutputValue');

        // Validation
        $this->RegisterPropertyString('DebugOutputValue', '');

        //Variables
        $this->RegisterVariableBoolean('Active', 'Module active', '~Switch', 0);
        $this->EnableAction('Active');
        $this->RegisterVariableBoolean('Reset', 'Module Reset (Pushfunction, returns to false) ', '~Switch', 1);
        $this->EnableAction('Reset');
        $this->RegisterVariableBoolean('Update', 'Recalculate Output  (Pushfunction, returns to false)', '~Switch', 2);
        $this->EnableAction('Update');

        $this->RegisterVariableFloat('TargetValue', ' Target Value', '~Temperature', 3);
        $this->EnableAction('TargetValue');

        $this->RegisterVariableFloat('ActualValue', ' Actual Value', '~Temperature', 4);
        $this->EnableAction('ActualValue');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        # IPS_LogMessage("MessageSink", "Message from SenderID ".$SenderID." with Message ".$Message."\r\n Data: ".print_r($Data, true));

        // Trigger ReCalc either timer based or based InputValue based
        if (($Message == VM_UPDATE)  and ($this->ReadPropertyInteger('RecalcInterval') == 0)) {
        #    SetValue($this->GetIDForIdent('TargetValue'), GetValueFloat($this->ReadPropertyInteger('TargetVariableID')));
            SetValue($this->GetIDForIdent('ActualValue'), GetValueFloat($this->ReadPropertyInteger('ActualVariableID')));
            $this->UpdateOutputValue();
        }

        if ($SenderID == 'ReCalc') {
            $this->UpdateOutputValue();
        }
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $eid = @$this->GetIDForIdent('SourceTrigger');
        if ($eid) {
            IPS_DeleteEvent($eid);
        }

        //Delete all registrations in order to readd them
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($senderID, $message);
            }
        }

        //Messages

        $TargetVariableID = $this->ReadPropertyInteger('TargetVariableID');
        if (IPS_VariableExists($TargetVariableID)) {
            $this->RegisterMessage($TargetVariableID, VM_UPDATE);
            #    SetValue($this->GetIDForIdent('Value'), $this->Calculate(GetValue($TargetVariableID)));
        }

        $ActualVariableID = $this->ReadPropertyInteger('ActualVariableID');
        if (IPS_VariableExists($ActualVariableID)) {
            $this->RegisterMessage($ActualVariableID, VM_UPDATE);
            #    SetValue($this->GetIDForIdent('Value'), $this->Calculate(GetValue($ActualVariableID)));
        }

        //Delete all references in order to readd them
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Add references
        foreach ($this->GetReferenceList() as $reference) {
            $this->UnregisterReference($reference);
        }


        $this->SetTimerInterval('ReCalc', $this->ReadPropertyInteger('RecalcInterval') * 1000);

        // Avoid div/0
        if ($this->ReadPropertyBoolean('IntegrationMethode') == false) {
            $this->UpdateFormField("IntegrationTime", "value", 1);
        }

        $this->WriteAttributeInteger("PrevTimestamp", time());
    }

    public function UpdateOutputValue()
    {
        if (!$this->GetValue('Active')) {
            return;
        }

        $TargetValue = GetValueFloat($this->GetIDForIdent('TargetValue'));


        if (($this->ReadPropertyInteger('AverageInputCount') > 0) and ($this->GetIDForIdent('ActualValue')===true)){
            $ArchiveID = IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0];
            $InputVariableID = $this->GetIDForIdent('ActualValue');
            $ActualValue = AC_GetLoggedValues ( $ArchiveID, $InputVariableID, 0, time() ,$this->ReadPropertyInteger('AverageInputCount')); 
        }
        else {
            $ActualValue = GetValueFloat($this->GetIDForIdent('ActualValue'));
        }

            $Scale = $this->ReadPropertyFloat('Scale');

        // invert Output eg for Cooling
        // temporarly removed, cannot check if it works correct 
        /*
          if ($this->ReadPropertyBoolean('Invert') == false) {
              $ErrVal = $TargetValue - $ActualValue ;
          } else {
              $ErrVal = $ActualValue - $TargetValue;
          }
          */

        $ErrVal = $TargetValue - $ActualValue ;
        
        $PrevErrVal = $this->ReadAttributeFloat("PrevErr");
        $this->WriteAttributeFloat("PrevErr", $ErrVal);

        if ($this->ReadPropertyFloat('IFaktor') > 0) {
            // Summ errors with fixed integration interval
            if ($this->ReadPropertyBoolean('IntegrationMethode') == true) {
                if (time() - $this->ReadAttributeInteger("PrevTimestamp") > $this->ReadPropertyFloat('IntegrationTime') * 60) {
                    if (($ErrVal > 0) and ($this->ReadAttributeFloat('PrevOutput') < 95)) {
                        $this->WriteAttributeInteger("PrevTimestamp", time());
                        $OldSum = $this->ReadAttributeFloat("SummErr");
                        $this->WriteAttributeFloat("SummErr", $OldSum + $ErrVal);
                    }
                    if (($ErrVal < 0) and ($this->ReadAttributeFloat('PrevOutput') > 0)) {
                        $this->WriteAttributeInteger("PrevTimestamp", time());
                        $OldSum = $this->ReadAttributeFloat("SummErr");
                        $this->WriteAttributeFloat("SummErr", $OldSum + $ErrVal);
                    }
                }
            }
            // Summ errors with weighted integration interval
            else {
                $WeightFactor = (time() - $this->ReadAttributeInteger("PrevTimestamp")) / ($this->ReadPropertyFloat('IntegrationTime')*60) ;
                if (($ErrVal > 0) and ($this->ReadAttributeFloat('PrevOutput') < 95)) {
                    $this->WriteAttributeInteger("PrevTimestamp", time());
                    $OldSum = $this->ReadAttributeFloat("SummErr");
                    $this->WriteAttributeFloat("SummErr", $OldSum + ($ErrVal*$WeightFactor));
                }
                if (($ErrVal < 0) and ($this->ReadAttributeFloat('PrevOutput') > 0)) {
                    $this->WriteAttributeInteger("PrevTimestamp", time());
                    $OldSum = $this->ReadAttributeFloat("SummErr");
                    $this->WriteAttributeFloat("SummErr", $OldSum + ($ErrVal*$WeightFactor));
                }
            }
        }

        $PFaktor = $this->ReadPropertyFloat('PFaktor') * $ErrVal;
        $IFaktor = $this->ReadPropertyFloat('IFaktor') * $this->ReadAttributeFloat("SummErr");
        $DFaktor = $this->ReadPropertyFloat('DFaktor') * ($ErrVal - $PrevErrVal);

        $PIDOutputValue =  $PFaktor + $IFaktor + $DFaktor;
        $PIDOutputValue = $PIDOutputValue * (100 / $Scale);

        // Limit to 0-100%
        if ($PIDOutputValue > 100) {
            $PIDOutputValue = 100;
        }
        if ($PIDOutputValue < 0) {
            $PIDOutputValue = 0;
        }

        // Update Output only if changes are big enough (avoid actuator overload)
        if (abs($PIDOutputValue - $this->ReadAttributeFloat('PrevOutput')) > $this->ReadPropertyInteger('UpdateThres')) {
            $PIDOutputValue = round($PIDOutputValue, 0);
            $this->WriteAttributeFloat("PrevOutput", $PIDOutputValue);
            $this->SetValue("PIDOutputValue", $PIDOutputValue);
            self::startScript($this->ReadPropertyInteger('OutScriptID'), $PIDOutputValue);

            $IDOutputVariable = $this->ReadPropertyInteger('OutputVariableID');
            if (IPS_VariableExists($IDOutputVariable)) {
                if (IPS_GetVariable($IDOutputVariable)['VariableAction'] == 0) {
                    SetValue($IDOutputVariable, $PIDOutputValue);
                } else {
                    RequestAction($IDOutputVariable, $PIDOutputValue);
                }
            }
        }
    //    $PFaktor = $PFaktor * (100 / $Scale);
    //    $IFaktor = $IFaktor * (100 / $Scale);
    //    $DFaktor = $DFaktor * (100 / $Scale);

        $this->UpdateFormField("ProportialPart", "value", "$PFaktor");
        $this->UpdateFormField("IntegralPart", "value", "$IFaktor");
        $this->UpdateFormField("DifferentialPart", "value", "$DFaktor");
        $this->UpdateFormField("OutputValue", "value", "$PIDOutputValue");
    }

    /****************************************************************************** */

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Active':
                $this->SetActive($Value);
                $this->SendDebug("Module-Status set to: ", $Value, 0);
                break;

            case 'Update':
                IPS_Sleep(200);
                $this->SetValue('Update', false);
                $this->SendDebug("Recalculate:", $Value, 0);
                if ($Value == true) {
                    $this->UpdateOutputValue();
                }
                break;

            case "Reset":
                IPS_Sleep(200);
                $this->SetValue('Reset', false);
                $this->SendDebug("Instance Reset: ", $Value, 0);
                if ($Value == true) {
                    $this->ResetInstance();
                }
                break;

            case "TargetValue":
                SetValue($this->GetIDForIdent($Ident), $Value);
                $this->UpdateOutputValue();
                break;

            case "ActualValue":
                SetValue($this->GetIDForIdent($Ident), $Value);
                $this->UpdateOutputValue();
                break;

            default:
                throw new Exception('Invalid ident');
        }
    }

    /****************************************************************************** */
    // Public Functions
    public function SetActive(bool $Value)
    {
        $this->SetValue('Active', $Value);
    }

    public function SetTargetValue(float $Value)
    {
        SetValue($this->GetIDForIdent('TargetValue'), $Value);
    }

    public function SetActualValue(float $Value)
    {
        $this->SetValue(SetValue('ActualValue'), $Value);
    }

    public function ResetInstance()
    {
        $this->SetValue("PIDOutputValue", 0);
        $this->WriteAttributeFloat("SummErr", 0);
        $this->WriteAttributeInteger("PrevTimestamp", time());
        $this->WriteAttributeFloat("PrevOutput", 50);
        $this->UpdateOutputValue();
    }
/****************************************************************************** */
// Private Functins
    private static function startScript($scriptID, $PIDOutputValue)
    {
        if (!IPS_ScriptExists($scriptID)) {
            return false;
        }

        return IPS_RunScriptEx($scriptID, ['VALUE' => $PIDOutputValue, 'SENDER' => 'PID_Controller']);
    }
}
